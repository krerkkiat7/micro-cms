<?php

namespace App\Support\Front;

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemInfo;
use App\Models\ArticleTagInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * อ่านหมวดหมู่/บทความสำหรับหน้าบ้าน — กติกา "เผยแพร่": status = Y, ไม่ถูกลบ (SoftDeletes) และบทความต้องอยู่ในช่วงเผยแพร่
 * (publish_date <= now หรือว่าง, publish_down > now หรือว่าง — เหมือน widget ของโมดูล page) ข้อมูลตัวอย่าง is_temp = Y แสดงได้ตามปกติ
 * ข้อความตามภาษาที่ขอ (ยังไม่ได้แปล = ภาษาหลัก)
 */
final class ArticleReader
{
    public static function publishedCategory(int $id): ?ArticleCategoryInfo
    {
        return ArticleCategoryInfo::query()->where('status', 'Y')->with(['details', 'introImage'])->find($id);
    }

    public static function publishedArticle(int $id): ?ArticleItemInfo
    {
        return self::published(ArticleItemInfo::query())->find($id);
    }

    /**
     * ข้อมูลหมวดหมู่สำหรับหน้ารายการ
     *
     * @return array<string, mixed>
     */
    public static function category(ArticleCategoryInfo $category, string $lang): array
    {
        $detail = FrontLang::pick($category->details, $lang);

        return [
            'id' => (int) $category->id,
            'title' => trim((string) ($detail?->title ?? '')),
            'intro_text' => trim((string) ($detail?->intro_text ?? '')),
            'detail_html' => HtmlSanitizer::clean($detail?->detail),
            'slug' => $detail?->slug,
            'image' => FrontFile::fromFileInfo($category->introImage, 1280),
            'meta' => self::meta($detail),
            'slugs' => $category->details->mapWithKeys(fn ($d) => [$d->lang => $d->slug])->all(),
        ];
    }

    /**
     * รายการบทความในหมวดหมู่ (แบ่งหน้า) — เรียงตามวันที่เผยแพร่ล่าสุด
     *
     * @return array{data: list<array<string, mixed>>, current_page: int, last_page: int, per_page: int, total: int}
     */
    public static function listForCategory(int $categoryId, string $lang, int $perPage, int $page): array
    {
        $query = self::published(ArticleItemInfo::query())
            ->join('article_item_detail as d', fn ($join) => FrontLang::joinDetail($join, 'd', 'article_item_detail', 'article_item_info.id', $lang))
            ->leftJoin('file_info as img', function ($join) {
                $join->on('img.id', '=', 'article_item_info.intro_image_id')->where('img.status', 'Y')->whereNull('img.deleted_at');
            })
            ->whereNull('d.deleted_at')
            ->where('article_item_info.article_category_info_id', $categoryId)
            ->orderByRaw('COALESCE(article_item_info.publish_date, article_item_info.created_at) desc')
            ->orderByDesc('article_item_info.id')
            ->select([
                'article_item_info.id',
                'article_item_info.publish_date',
                'article_item_info.created_at',
                'article_item_info.view_amount',
                'd.title', 'd.intro_text', 'd.slug',
                'img.hash_name as image',
            ]);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'intro_text' => (string) ($row->intro_text ?? ''),
                'url' => FrontUrl::articleCategoryItem($lang, $categoryId, (int) $row->id, $row->slug),
                'image_url' => FrontFile::thumbnail($row->image, 640),
                'date' => optional($row->publish_date ?? $row->created_at)->format('Y-m-d\TH:i:sP'),
                'views' => (int) $row->view_amount,
            ])->values()->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * ข้อมูลรายละเอียดบทความ (หัวเรื่อง, part, แท็ก, วันที่, ยอดเข้าชม)
     *
     * @return array<string, mixed>
     */
    public static function article(ArticleItemInfo $article, string $lang): array
    {
        $article->loadMissing([
            'details', 'introImage',
            'parts' => fn ($query) => $query->where('status', 'Y'),
            'parts.details', 'parts.files.file', 'parts.files.coverImage',
            'tags' => fn ($query) => $query->where('article_tag_info.status', 'Y'),
            'tags.details',
        ]);

        $detail = FrontLang::pick($article->details, $lang);
        $published = $article->publish_date ?? $article->created_at;

        return [
            'id' => (int) $article->id,
            'category_id' => $article->article_category_info_id !== null ? (int) $article->article_category_info_id : null,
            'title' => trim((string) ($detail?->title ?? '')),
            'intro_text' => trim((string) ($detail?->intro_text ?? '')),
            'slug' => $detail?->slug,
            'image' => FrontFile::fromFileInfo($article->introImage, 1280),
            'published_at' => optional($published)->format('Y-m-d\TH:i:sP'),
            'updated_at' => optional($article->updated_at)->format('Y-m-d\TH:i:sP'),
            'publish_down' => optional($article->publish_down)->format('Y-m-d H:i:s'),
            'views' => (int) $article->view_amount,
            'parts' => FrontParts::map($article->parts, $lang),
            'tags' => $article->tags
                ->map(fn (ArticleTagInfo $tag) => trim((string) (FrontLang::pick($tag->details, $lang, 'name')?->name ?? '')))
                ->filter()
                ->values()
                ->all(),
            'meta' => self::meta($detail),
            'slugs' => $article->details->mapWithKeys(fn ($d) => [$d->lang => $d->slug])->all(),
        ];
    }

    /**
     * @return array{title: string|null, description: string|null, keywords: string|null, og_title: string|null, og_description: string|null}
     */
    private static function meta(?object $detail): array
    {
        return [
            'title' => $detail?->meta_title ?: null,
            'description' => $detail?->meta_description ?: null,
            'keywords' => $detail?->meta_keywords ?: null,
            'og_title' => $detail?->og_title ?: null,
            'og_description' => $detail?->og_description ?: null,
        ];
    }

    private static function published(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('article_item_info.status', 'Y')
            ->where(fn ($q) => $q->whereNull('article_item_info.publish_date')->orWhere('article_item_info.publish_date', '<=', $now))
            ->where(fn ($q) => $q->whereNull('article_item_info.publish_down')->orWhere('article_item_info.publish_down', '>', $now));
    }
}
