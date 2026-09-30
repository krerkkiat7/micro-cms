<?php

namespace App\Support\Front;

use App\Support\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use XMLWriter;

/**
 * sitemap.xml ของหน้าบ้าน — **อิงตามเมนูหน้าบ้านที่เผยแพร่** (FrontMenuResolver::publishedTargets(): status Y + พาเรนต์แสดงทั้งสาย)
 * ดู docs/PRD-front.md §sitemap
 *
 * - /sitemap.xml            sitemap index ชี้ไฟล์ย่อยด้านล่าง (พร้อม lastmod)
 * - /sitemap-main.xml       หน้าแรก (เมื่อมี Intropage เผยแพร่) + หน้าเพจ/หมวดหมู่/บทความ/ติดต่อเรา ที่เมนูชี้ตรง
 * - /sitemap-article-{n}.xml บทความที่เผยแพร่ในหมวดหมู่ที่มีเมนู แบ่งไฟล์ละ front.sitemap.article_per_file เรื่อง
 *
 * เนื้อหาที่ไม่มีเมนูที่เผยแพร่ชี้ไปถึง / หน้าแท็ก ไม่อยู่ใน sitemap (ตั้งใจ); ข้อมูลตัวอย่าง is_temp = Y รวมตามปกติ
 * <loc> = canonical เดียวกับที่หน้านั้นประกาศ (slug ของภาษาที่ขอถ้ามีหัวเรื่อง ไม่งั้นภาษาหลัก — FrontLang::pick) + hreflang ทุกภาษา + x-default
 *
 * ผลลัพธ์ (XML ที่ render แล้ว) cache ผ่าน FrontCache — ล้างอัตโนมัติเมื่อบันทึกเมนู/เนื้อหาในหลังบ้าน, TTL คุมการถึง/หมดช่วงเผยแพร่ตามเวลา
 * key แยกตาม host เพราะ URL ใน XML เป็น URL เต็มของ request (เปิดได้หลายโดเมน เช่น www/ไม่มี www)
 */
final class Sitemap
{
    private const NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private const NS_XHTML = 'http://www.w3.org/1999/xhtml';

    private const NS_IMAGE = 'http://www.google.com/schemas/sitemap-image/1.1';

    /**
     * sitemap index — null lastmod = ไม่ใส่
     */
    public static function index(): string
    {
        return self::cached('index', function () {
            $files = [['loc' => route('front.sitemap.main'), 'lastmod' => self::main()['lastmod']]];

            for ($n = 1; $n <= self::articleFileCount(); $n++) {
                $files[] = ['loc' => route('front.sitemap.article', ['n' => $n]), 'lastmod' => self::articleFile($n)['lastmod']];
            }

            $xml = self::writer();
            $xml->startElement('sitemapindex');
            $xml->writeAttribute('xmlns', self::NS);

            foreach ($files as $file) {
                $xml->startElement('sitemap');
                $xml->writeElement('loc', $file['loc']);

                if ($file['lastmod'] !== null) {
                    $xml->writeElement('lastmod', $file['lastmod']);
                }

                $xml->endElement();
            }

            $xml->endElement();

            return ['xml' => $xml->outputMemory(), 'lastmod' => null];
        })['xml'];
    }

    /**
     * @return array{xml: string, lastmod: string|null}
     */
    public static function main(): array
    {
        return self::cached('main', function () {
            $targets = FrontMenuResolver::publishedTargets();
            $entries = [];

            // หน้าแรก /{lang} — เป็นหน้าจริงเฉพาะเมื่อมี Intropage เผยแพร่อยู่ (ไม่งั้น redirect ไปเมนู is_home ซึ่งอยู่ใน sitemap ตามเมนูอยู่แล้ว)
            if (IntropageResolver::current(Setting::defaultLanguage()) !== null) {
                $entries[] = self::entry(fn (string $lang) => FrontUrl::home($lang));
            }

            $categoryIds = self::menuCategoryIds();
            array_push($entries, ...self::pageEntries($targets['page']));
            array_push($entries, ...self::categoryEntries($categoryIds));

            // บทความที่เมนูชี้ตรง — ที่อยู่ในหมวดหมู่ที่มีเมนูแล้วจะอยู่ในไฟล์ sitemap-article-* (ไม่ใส่ซ้ำ)
            $direct = self::publishedArticles()
                ->whereIn('i.id', $targets['article_item'] ?: [0])
                ->when($categoryIds !== [], fn ($query) => $query->where(fn ($q) => $q
                    ->whereNull('i.article_category_info_id')
                    ->orWhereNotIn('i.article_category_info_id', $categoryIds)))
                ->orderBy('i.id')
                ->get(self::ARTICLE_COLUMNS);
            array_push($entries, ...self::articleEntries($direct));

            if ($targets['contactus']) {
                $entries[] = self::entry(fn (string $lang) => FrontUrl::contactus($lang));
            }

            return self::urlset($entries);
        });
    }

    /**
     * ไฟล์บทความลำดับที่ $n (เริ่ม 1) — null = ไม่มีไฟล์นี้
     *
     * @return array{xml: string, lastmod: string|null}|null
     */
    public static function articleFile(int $n): ?array
    {
        if ($n < 1 || $n > self::articleFileCount()) {
            return null;
        }

        return self::cached("article.{$n}", function () use ($n) {
            $perFile = self::articlePerFile();

            $rows = self::menuCategoryArticles()
                ->orderBy('i.id')
                ->offset(($n - 1) * $perFile)
                ->limit($perFile)
                ->get(self::ARTICLE_COLUMNS);

            return self::urlset(self::articleEntries($rows));
        });
    }

    public static function articleFileCount(): int
    {
        return self::cached('article.count', fn () => (int) ceil(self::menuCategoryArticles()->count() / self::articlePerFile()));
    }

    // -----------------------------------------------------------------------------------------------------------------
    // แหล่งข้อมูล

    private const ARTICLE_COLUMNS = ['i.id', 'i.article_category_info_id', 'i.publish_date', 'i.updated_at', 'f.hash_name'];

    /**
     * บทความที่เผยแพร่อยู่ (เงื่อนไขเดียวกับ ArticleReader::published()) + รูปหน้าปก
     */
    private static function publishedArticles()
    {
        $now = now();

        return DB::table('article_item_info as i')
            ->leftJoin('file_info as f', fn ($join) => $join->on('f.id', '=', 'i.intro_image_id')->where('f.status', 'Y')->whereNull('f.deleted_at'))
            ->whereNull('i.deleted_at')
            ->where('i.status', 'Y')
            ->where(fn ($q) => $q->whereNull('i.publish_date')->orWhere('i.publish_date', '<=', $now))
            ->where(fn ($q) => $q->whereNull('i.publish_down')->orWhere('i.publish_down', '>', $now));
    }

    private static function menuCategoryArticles()
    {
        return self::publishedArticles()->whereIn('i.article_category_info_id', self::menuCategoryIds() ?: [0]);
    }

    /**
     * หมวดหมู่ที่เมนูที่เผยแพร่ชี้ไป และหมวดหมู่นั้นเผยแพร่อยู่
     *
     * @return list<int>
     */
    private static function menuCategoryIds(): array
    {
        $ids = FrontMenuResolver::publishedTargets()['article_category'];

        return $ids === [] ? [] : self::publishedCategoryIds($ids);
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private static function publishedCategoryIds(array $ids): array
    {
        return DB::table('article_category_info')
            ->whereIn('id', $ids)
            ->where('status', 'Y')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    private static function pageEntries(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('page_item_info as i')
            ->leftJoin('file_info as f', fn ($join) => $join->on('f.id', '=', 'i.intro_image_id')->where('f.status', 'Y')->whereNull('f.deleted_at'))
            ->whereIn('i.id', $ids)
            ->where('i.status', 'Y')
            ->whereNull('i.deleted_at')
            ->orderBy('i.id')
            ->get(['i.id', 'i.updated_at', 'i.layout_updated_at', 'f.hash_name']);

        $details = self::details('page_item_detail', $rows->pluck('id')->all());

        return $rows->map(fn ($row) => self::entry(
            fn (string $lang) => FrontUrl::page($lang, (int) $row->id, self::pick($details, (int) $row->id, $lang)?->slug),
            [$row->updated_at, $row->layout_updated_at, ...self::detailDates($details, (int) $row->id)],
            $row->hash_name,
        ))->all();
    }

    /**
     * @param  list<int>  $ids  หมวดหมู่ที่เผยแพร่อยู่แล้ว
     * @return list<array<string, mixed>>
     */
    private static function categoryEntries(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('article_category_info as i')
            ->leftJoin('file_info as f', fn ($join) => $join->on('f.id', '=', 'i.intro_image_id')->where('f.status', 'Y')->whereNull('f.deleted_at'))
            ->whereIn('i.id', $ids)
            ->orderBy('i.sort_order')
            ->orderBy('i.id')
            ->get(['i.id', 'i.updated_at', 'f.hash_name']);

        // หน้าหมวดหมู่เปลี่ยนเมื่อมีบทความใหม่/แก้บทความในหมวด — lastmod รวมบทความล่าสุดด้วย
        $latest = self::publishedArticles()
            ->whereIn('i.article_category_info_id', $ids)
            ->groupBy('i.article_category_info_id')
            ->selectRaw('i.article_category_info_id as category_id, max(i.updated_at) as updated_at, max(i.publish_date) as publish_date')
            ->get()
            ->keyBy('category_id');

        $details = self::details('article_category_detail', $ids);

        return $rows->map(fn ($row) => self::entry(
            fn (string $lang) => FrontUrl::articleCategory($lang, (int) $row->id, self::pick($details, (int) $row->id, $lang)?->slug),
            [$row->updated_at, $latest->get($row->id)?->updated_at, $latest->get($row->id)?->publish_date, ...self::detailDates($details, (int) $row->id)],
            $row->hash_name,
        ))->all();
    }

    /**
     * canonical ของบทความ = ผ่านหมวดหมู่เมื่อหมวดหมู่เผยแพร่อยู่ ไม่งั้น URL ตรง (ตรงกับ Front\Article\ArticleItemController)
     *
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private static function articleEntries(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $details = self::details('article_item_detail', $rows->pluck('id')->all());
        $publishedCategories = array_flip(self::publishedCategoryIds($rows->pluck('article_category_info_id')->filter()->unique()->values()->all()));

        return $rows->map(function ($row) use ($details, $publishedCategories) {
            $id = (int) $row->id;
            $categoryId = $row->article_category_info_id !== null ? (int) $row->article_category_info_id : null;
            $viaCategory = $categoryId !== null && isset($publishedCategories[$categoryId]);

            return self::entry(
                fn (string $lang) => $viaCategory
                    ? FrontUrl::articleCategoryItem($lang, $categoryId, $id, self::pick($details, $id, $lang)?->slug)
                    : FrontUrl::articleItem($lang, $id, self::pick($details, $id, $lang)?->slug),
                [$row->updated_at, $row->publish_date, ...self::detailDates($details, $id)],
                $row->hash_name,
            );
        })->all();
    }

    /**
     * แถว detail ทุกภาษาที่ไม่ถูกลบ จัดกลุ่มตาม id
     *
     * @param  list<int|string>  $ids
     * @return Collection<int, Collection<int, object>>
     */
    private static function details(string $table, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return DB::table($table)
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get(['id', 'lang', 'title', 'slug', 'updated_at'])
            ->groupBy('id');
    }

    private static function pick(Collection $details, int $id, string $lang): ?object
    {
        return FrontLang::pick($details->get($id) ?? [], $lang);
    }

    /**
     * @return list<string|null>
     */
    private static function detailDates(Collection $details, int $id): array
    {
        return ($details->get($id) ?? collect())->pluck('updated_at')->all();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // XML

    /**
     * 1 เนื้อหา → 1 <url> ต่อภาษาที่เปิด (สร้างตอน render)
     *
     * @param  callable(string): string  $urlFor
     * @param  list<mixed>  $dates  ค่าวันที่ที่เกี่ยวข้อง — lastmod = ค่าล่าสุดที่ไม่เกินเวลาปัจจุบัน
     * @return array{urls: array<string, string>, lastmod: string|null, image: string|null}
     */
    private static function entry(callable $urlFor, array $dates = [], ?string $imageHash = null): array
    {
        $urls = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $urls[$lang] = $urlFor($lang);
        }

        return ['urls' => $urls, 'lastmod' => self::latest($dates), 'image' => FrontFile::url($imageHash)];
    }

    /**
     * @param  list<array{urls: array<string, string>, lastmod: string|null, image: string|null}>  $entries
     * @return array{xml: string, lastmod: string|null}
     */
    private static function urlset(array $entries): array
    {
        $default = Setting::defaultLanguage();
        $multiLang = count(Setting::selectedLanguages()) > 1;
        $seen = [];
        $lastmod = null;

        $xml = self::writer();
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', self::NS);
        $xml->writeAttribute('xmlns:xhtml', self::NS_XHTML);
        $xml->writeAttribute('xmlns:image', self::NS_IMAGE);

        foreach ($entries as $entry) {
            foreach ($entry['urls'] as $url) {
                if (isset($seen[$url])) {
                    continue;
                }

                $seen[$url] = true;
                $xml->startElement('url');
                $xml->writeElement('loc', $url);

                if ($entry['lastmod'] !== null) {
                    $xml->writeElement('lastmod', $entry['lastmod']);
                }

                // hreflang ต้องอ้างถึงกันครบทุกภาษา (รวมตัวเอง) + x-default = ภาษาหลัก — ตรงกับ <link rel="alternate"> ใน <head>
                if ($multiLang) {
                    foreach ([...$entry['urls'], 'x-default' => $entry['urls'][$default] ?? reset($entry['urls'])] as $hreflang => $href) {
                        $xml->startElement('xhtml:link');
                        $xml->writeAttribute('rel', 'alternate');
                        $xml->writeAttribute('hreflang', (string) $hreflang);
                        $xml->writeAttribute('href', $href);
                        $xml->endElement();
                    }
                }

                if ($entry['image'] !== null) {
                    $xml->startElement('image:image');
                    $xml->writeElement('image:loc', $entry['image']);
                    $xml->endElement();
                }

                $xml->endElement();
            }

            if ($entry['lastmod'] !== null && ($lastmod === null || $entry['lastmod'] > $lastmod)) {
                $lastmod = $entry['lastmod'];
            }
        }

        $xml->endElement();

        return ['xml' => $xml->outputMemory(), 'lastmod' => $lastmod];
    }

    private static function writer(): XMLWriter
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8');

        return $xml;
    }

    /**
     * ค่าวันที่ล่าสุด (ไม่เกินตอนนี้ — publish_date ในอนาคตไม่นับ) ในรูปแบบ W3C Datetime
     *
     * @param  list<mixed>  $dates
     */
    private static function latest(array $dates): ?string
    {
        $now = CarbonImmutable::now();
        $latest = null;

        foreach ($dates as $date) {
            if ($date === null || $date === '') {
                continue;
            }

            $parsed = CarbonImmutable::parse((string) $date);

            if ($parsed->lessThanOrEqualTo($now) && ($latest === null || $parsed->greaterThan($latest))) {
                $latest = $parsed;
            }
        }

        return $latest?->toAtomString();
    }

    // -----------------------------------------------------------------------------------------------------------------

    private static function articlePerFile(): int
    {
        return max(1, (int) config('front.sitemap.article_per_file', 1000));
    }

    /**
     * @template T
     *
     * @param  \Closure(): T  $build
     * @return T
     */
    private static function cached(string $name, \Closure $build): mixed
    {
        $host = md5(request()->getSchemeAndHttpHost());

        return FrontCache::remember("sitemap.{$host}.{$name}", (int) config('front.sitemap.ttl', 3600), $build);
    }
}
