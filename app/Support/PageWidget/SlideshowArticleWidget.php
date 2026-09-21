<?php

namespace App\Support\PageWidget;

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemInfo;
use App\Models\PageItemWidgetSlideshowArticle;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Builder;

/**
 * widget "Slideshow จาก article" — ภาพเต็มภาพเดียวที่สไลด์ได้ ข้อมูลมาจากบทความ (article_item_*) ของหมวดหมู่ที่เลือก ใช้รูปหน้าปกของบทความ
 * (บทความที่ไม่มีรูปหน้าปกไม่ถูกแสดง) ลิงก์ = หน้าบทความ (มีได้เมื่อบทความมี slug ของภาษานั้น) การตั้งค่าอื่นเหมือน Slideshow จาก banner ทุกอย่าง
 * (ดู SlideshowWidget) ต่างที่บทความไม่มีคอลัมน์ "ลำดับ" ต่อรายการ (มีแต่หมวดหมู่) จึงเรียงได้เฉพาะตามวันที่เผยแพร่
 * ตาราง `page_item_widget_slideshowarticle` (PK = `page_item_widget.id`)
 */
class SlideshowArticleWidget extends SlideshowWidget
{
    public const TYPE = 'slideshowarticle';

    /** วันที่เผยแพร่ล่าสุด/เก่าสุด (บทความไม่มี sort_order ต่อรายการ) */
    public const SORTS = ['publish_desc', 'publish_asc'];

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'slideshowArticle';
    }

    protected function model(): string
    {
        return PageItemWidgetSlideshowArticle::class;
    }

    protected function categoryField(): string
    {
        return 'article_category_info_id';
    }

    protected function categoryTable(): string
    {
        return 'article_category_info';
    }

    protected function categoryLabel(): string
    {
        return 'หมวดหมู่ article';
    }

    protected function sorts(): array
    {
        return self::SORTS;
    }

    public function options(): array
    {
        $defaultLang = Setting::defaultLanguage();

        // ชื่อหมวดหมู่ = ภาษาหลัก เรียงตามลำดับของหมวดหมู่ (แล้วชื่อ) เหมือนหน้าจัดการบทความ
        $categories = ArticleCategoryInfo::query()
            ->join('article_category_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_category_info.id')->where('d.lang', $defaultLang);
            })
            ->whereNull('d.deleted_at')
            ->where('article_category_info.status', 'Y')
            ->orderBy('article_category_info.sort_order')
            ->orderBy('d.title')
            ->get(['article_category_info.id', 'd.title as title'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => $row->title])
            ->values()
            ->all();

        return ['article_categories' => $categories];
    }

    protected function previewQuery(int $categoryId): Builder
    {
        $defaultLang = Setting::defaultLanguage();
        $now = now();

        return ArticleItemInfo::query()
            ->join('article_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_item_info.id')->where('d.lang', $defaultLang);
            })
            // ต้องมีรูปหน้าปกที่ใช้งานได้ (ภาพคือตัวเนื้อหาของ slideshow)
            ->join('file_info as img', function ($join) {
                $join->on('img.id', '=', 'article_item_info.intro_image_id')
                    ->where('img.status', 'Y')
                    ->whereNull('img.deleted_at');
            })
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->where('article_item_info.article_category_info_id', $categoryId)
            ->where('article_item_info.status', 'Y')
            ->where(fn ($q) => $q->whereNull('article_item_info.publish_date')->orWhere('article_item_info.publish_date', '<=', $now))
            ->where(fn ($q) => $q->whereNull('article_item_info.publish_down')->orWhere('article_item_info.publish_down', '>', $now))
            ->selectRaw("article_item_info.id, img.hash_name as image, d.title, d.intro_text, (d.slug is not null and d.slug <> '') as has_link");
    }

    protected function orderPreview(Builder $query, string $sortBy): void
    {
        $direction = $sortBy === 'publish_asc' ? 'asc' : 'desc';

        $query->orderByRaw("COALESCE(article_item_info.publish_date, article_item_info.created_at) {$direction}")
            ->orderBy('article_item_info.id'); // tie-breaker ให้ลำดับเสถียร
    }
}
