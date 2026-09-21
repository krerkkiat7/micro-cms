<?php

namespace App\Support\PageWidget;

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemInfo;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Builder;

/**
 * แหล่งข้อมูล "article" ของ widget ที่ดึงรายการจากหมวดหมู่ (CategoryListWidget) — หมวดหมู่ article, ตัวเลือกการเรียงลำดับ
 * และ query บทความที่เผยแพร่อยู่ ใช้ร่วมกันระหว่าง Slideshow/Slideset จาก article (คลาสที่ใช้ trait ต้อง extends CategoryListWidget)
 * บทความไม่มีคอลัมน์ "ลำดับ" ต่อรายการ (มีแต่หมวดหมู่) จึงเรียงได้เฉพาะตามวันที่เผยแพร่
 */
trait ReadsArticles
{
    /** วันที่เผยแพร่ล่าสุด/เก่าสุด (บทความไม่มี sort_order ต่อรายการ) */
    public const SORTS = ['publish_desc', 'publish_asc'];

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

    /**
     * บทความของหมวดหมู่ที่เผยแพร่อยู่ (`status = Y`, ไม่ถูกลบ, อยู่ในช่วงเผยแพร่) พร้อมข้อมูลภาษาหลักเป็น `d` และรูปหน้าปกเป็น `img`
     * `$requireImage` = true → เฉพาะบทความที่มีรูปหน้าปกที่ใช้งานได้ (inner join), false → left join (ไม่มีรูปก็แสดง)
     * ผู้เรียกต้อง select คอลัมน์เอง
     */
    protected function articleQuery(int $categoryId, bool $requireImage): Builder
    {
        $defaultLang = Setting::defaultLanguage();
        $now = now();
        $imageJoin = function ($join) {
            $join->on('img.id', '=', 'article_item_info.intro_image_id')
                ->where('img.status', 'Y')
                ->whereNull('img.deleted_at');
        };

        $query = ArticleItemInfo::query()
            ->join('article_item_detail as d', function ($join) use ($defaultLang) {
                $join->on('d.id', '=', 'article_item_info.id')->where('d.lang', $defaultLang);
            });

        $requireImage
            ? $query->join('file_info as img', $imageJoin)
            : $query->leftJoin('file_info as img', $imageJoin);

        return $query
            ->whereNull('d.deleted_at') // join ตรง ไม่ผ่าน scope ของ model ต้องกันเองไม่ให้ดึงแถวที่ถูกลบ
            ->where('article_item_info.article_category_info_id', $categoryId)
            ->where('article_item_info.status', 'Y')
            ->where(fn ($q) => $q->whereNull('article_item_info.publish_date')->orWhere('article_item_info.publish_date', '<=', $now))
            ->where(fn ($q) => $q->whereNull('article_item_info.publish_down')->orWhere('article_item_info.publish_down', '>', $now));
    }

    protected function orderPreview(Builder $query, string $sortBy): void
    {
        $direction = $sortBy === 'publish_asc' ? 'asc' : 'desc';

        $query->orderByRaw("COALESCE(article_item_info.publish_date, article_item_info.created_at) {$direction}")
            ->orderBy('article_item_info.id'); // tie-breaker ให้ลำดับเสถียร
    }
}
