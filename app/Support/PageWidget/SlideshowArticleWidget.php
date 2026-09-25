<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetSlideshowArticle;
use Illuminate\Database\Eloquent\Builder;

/**
 * widget "Slideshow จาก article" — ภาพเต็มภาพเดียวที่สไลด์ได้ ข้อมูลมาจากบทความ (article_item_*) ของหมวดหมู่ที่เลือก ใช้รูปหน้าปกของบทความ
 * (บทความที่ไม่มีรูปหน้าปกไม่ถูกแสดง) ลิงก์ = หน้าบทความ (บทความทุกใบมีลิงก์ ไม่ขึ้นกับว่ามี slug หรือไม่) การตั้งค่าอื่นเหมือน Slideshow จาก banner ทุกอย่าง
 * (ดู SlideshowWidget) ต่างที่เรียงได้เฉพาะตามวันที่เผยแพร่ (ดู ReadsArticles)
 * ตาราง `page_item_widget_slideshowarticle` (PK = `page_item_widget.id`)
 */
class SlideshowArticleWidget extends SlideshowWidget
{
    use ReadsArticles;

    public const TYPE = 'slideshowarticle';

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

    protected function previewQuery(int $categoryId, ?string $lang = null): Builder
    {
        // ต้องมีรูปหน้าปกที่ใช้งานได้ (ภาพคือตัวเนื้อหาของ slideshow)
        return $this->articleQuery($categoryId, requireImage: true, lang: $lang)
            ->selectRaw('article_item_info.id, img.hash_name as image, d.title, d.intro_text, 1 as has_link');
    }
}
