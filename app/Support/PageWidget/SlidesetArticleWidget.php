<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetSlidesetArticle;
use App\Models\PageItemWidgetSlidesetArticleDetail;
use Illuminate\Database\Eloquent\Builder;

/**
 * widget "Slideset จาก article" — การ์ดบทความหลายใบที่เลื่อนดูได้ (รูป, หัวเรื่อง, ข้อความเกริ่นนำ, วันที่เผยแพร่, จำนวนเข้าชม — แต่ละส่วนเปิด/ปิดและ
 * จัดรูปแบบตัวอักษรเองได้) + ปุ่ม "อ่านทั้งหมด" (ดู HasReadAllButton — ใช้ร่วมกับ Grid จาก article) ตั้งค่าส่วนร่วมอยู่ใน SlidesetWidget
 * ต่างจาก Slideshow ตรงที่บทความที่ไม่มีรูปหน้าปกก็ยังแสดง (ไม่มีรูป = กรอบว่าง) และแต่ละส่วนของการ์ดกดลิงก์ไปหน้าบทความได้แยกกัน
 * ตาราง `page_item_widget_slidesetarticle` (+ `_detail` เก็บข้อความปุ่มแยกภาษา) PK = `page_item_widget.id`
 */
class SlidesetArticleWidget extends SlidesetWidget
{
    use HasReadAllButton;
    use ReadsArticles;

    public const TYPE = 'slidesetarticle';

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'slidesetArticle';
    }

    protected function model(): string
    {
        return PageItemWidgetSlidesetArticle::class;
    }

    protected function detailModel(): ?string
    {
        return PageItemWidgetSlidesetArticleDetail::class;
    }

    protected function detailFields(): array
    {
        return $this->readAllDetailFields();
    }

    protected function introShownByDefault(): string
    {
        return 'Y';
    }

    protected function extraFields(): array
    {
        return $this->metaFields('date', 'วันที่เผยแพร่', showDefault: 'Y')
            + $this->metaFields('views', 'จำนวนเข้าชม', showDefault: 'N')
            + $this->readAllFields();
    }

    protected function normalize(array $values): array
    {
        return $this->normalizeReadAllUrl(parent::normalize($values));
    }

    protected function previewQuery(int $categoryId, ?string $lang = null): Builder
    {
        // ไม่บังคับมีรูปหน้าปก (การ์ดแสดงกรอบว่างแทน) — วันที่ที่แสดงใช้วันที่เผยแพร่ (ถ้าไม่มีใช้วันที่สร้าง เหมือนตอนเรียงลำดับ)
        return $this->articleQuery($categoryId, requireImage: false, lang: $lang)
            ->selectRaw('article_item_info.id, img.hash_name as image, d.title, d.intro_text, COALESCE(article_item_info.publish_date, article_item_info.created_at) as shown_date, article_item_info.view_amount');
    }

    protected function previewRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'image' => $row->image, // null = บทความไม่มีรูปหน้าปก
            'title' => $row->title ?? '',
            'intro_text' => $row->intro_text ?? '',
            'date' => $row->shown_date !== null ? substr((string) $row->shown_date, 0, 10) : null, // Y-m-d
            'views' => (int) $row->view_amount,
        ];
    }
}
