<?php

namespace App\Support\Front;

use App\Support\AppAsset;
use App\Support\Setting;
use Illuminate\Support\Str;

/**
 * สร้างข้อมูล SEO / AEO / GEO ของหน้าบ้าน 1 หน้า — ส่งเป็น prop `seo` ให้ทั้ง app.blade.php (render meta ฝั่ง server ตั้งแต่ HTML แรก
 * ให้ crawler/บอท AI ที่ไม่รัน JavaScript อ่านได้) และ Components/Front/SeoHead.vue (อัปเดต <head> ตอนเปลี่ยนหน้าแบบ Inertia)
 *
 * - title / description / keywords / canonical / robots
 * - Open Graph + Twitter card
 * - hreflang ทุกภาษาที่เปิดใช้ + x-default (ภาษาหลัก)
 * - JSON-LD (schema.org): WebSite + Organization ทุกหน้า และตามชนิดหน้า (WebPage / CollectionPage / Article + BreadcrumbList)
 */
final class SeoMeta
{
    /**
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     keywords?: string|null,
     *     og_title?: string|null,
     *     og_description?: string|null,
     *     image?: string|null,
     *     type?: string,
     *     canonical: string,
     *     alternates?: array<string, string>,
     *     breadcrumb?: list<array{name: string, url: string|null}>,
     *     schema?: array<string, mixed>|null,
     *     noindex?: bool,
     * }  $data
     * @return array<string, mixed>
     */
    public static function make(string $lang, array $data): array
    {
        $siteName = Setting::siteName();
        $title = trim($data['title']) !== '' ? trim($data['title']) : $siteName;
        $description = self::plain($data['description'] ?? null) ?: self::plain(Setting::get('site', 'site_description'));
        $image = $data['image'] ?? null;
        $defaultLang = Setting::defaultLanguage();
        $alternates = $data['alternates'] ?? [];

        $links = [];

        foreach ($alternates as $code => $url) {
            $links[] = ['hreflang' => $code, 'href' => $url];
        }

        if (isset($alternates[$defaultLang])) {
            $links[] = ['hreflang' => 'x-default', 'href' => $alternates[$defaultLang]];
        }

        $jsonLd = [self::website($lang), self::organization()];

        if (! empty($data['breadcrumb'])) {
            $jsonLd[] = self::breadcrumbList($data['breadcrumb']);
        }

        if (! empty($data['schema'])) {
            $jsonLd[] = ['@context' => 'https://schema.org', ...$data['schema']];
        }

        return [
            'title' => $title,
            'fullTitle' => $title === $siteName ? $siteName : "{$title} - {$siteName}",
            'description' => $description,
            'keywords' => self::plain($data['keywords'] ?? null),
            'canonical' => $data['canonical'],
            'robots' => ($data['noindex'] ?? false) ? 'noindex, follow' : 'index, follow, max-image-preview:large',
            'og' => [
                'type' => $data['type'] ?? 'website',
                'title' => self::plain($data['og_title'] ?? null) ?: $title,
                'description' => self::plain($data['og_description'] ?? null) ?: $description,
                'image' => $image,
                'url' => $data['canonical'],
                'site_name' => $siteName,
                'locale' => self::ogLocale($lang),
            ],
            'twitter' => $image ? 'summary_large_image' : 'summary',
            'alternates' => $links,
            'jsonLd' => $jsonLd,
        ];
    }

    /**
     * ข้อความล้วนสำหรับ meta (ตัดแท็ก/ช่องว่างซ้ำ/ความยาวเกิน)
     */
    public static function plain(?string $value, int $limit = 300): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');

        return Str::limit($text, $limit, '…');
    }

    /**
     * @return array<string, mixed>
     */
    private static function website(string $lang): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => Setting::siteName(),
            'url' => FrontUrl::home($lang),
            'inLanguage' => $lang,
        ];
    }

    /**
     * ข้อมูลองค์กร (GEO/AEO — ให้เครื่องมือค้นหา/AI รู้ว่าใครเป็นเจ้าของเว็บ และติดต่ออย่างไร)
     *
     * @return array<string, mixed>
     */
    private static function organization(): array
    {
        $social = collect(Setting::group('social'))
            ->filter(fn ($value) => is_string($value) && preg_match('~^https?://~i', $value))
            ->values()
            ->all();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Setting::get('site', 'copyright_owner') ?? Setting::siteName(),
            'url' => url('/'),
            'logo' => AppAsset::logo() ? route('app.logo') : null,
            'email' => Setting::get('contact', 'email'),
            'telephone' => Setting::get('contact', 'phone'),
            'address' => Setting::get('contact', 'address_'.Setting::defaultLanguage()),
            'sameAs' => $social ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  list<array{name: string, url: string|null}>  $items
     * @return array<string, mixed>
     */
    private static function breadcrumbList(array $items): array
    {
        $position = 0;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_map(function (array $item) use (&$position) {
                $position++;

                return array_filter([
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ], fn ($value) => $value !== null);
            }, array_filter($items, fn ($item) => trim((string) $item['name']) !== ''))),
        ];
    }

    private static function ogLocale(string $lang): string
    {
        return match ($lang) {
            'th' => 'th_TH',
            'en' => 'en_US',
            default => $lang,
        };
    }
}
