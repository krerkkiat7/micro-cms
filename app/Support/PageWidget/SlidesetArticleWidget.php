<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetSlidesetArticle;
use Illuminate\Database\Eloquent\Builder;

/**
 * widget "Slideset จาก article" — การ์ดบทความหลายใบที่เลื่อนดูได้ (รูป, หัวเรื่อง, ข้อความเกริ่นนำ, วันที่เผยแพร่, จำนวนเข้าชม — แต่ละส่วนเปิด/ปิดและ
 * จัดรูปแบบตัวอักษรเองได้) จำนวนการ์ดต่อแถวกำหนดตามขนาดหน้าจอ (PC / Notebook / Tablet / Mobile) เลื่อนทีละ "หน้า" (ครั้งละเท่าจำนวนต่อแถว)
 * ต่างจาก Slideshow ตรงที่บทความที่ไม่มีรูปหน้าปกก็ยังแสดง (ไม่มีรูป = กรอบว่าง) และแต่ละส่วนของการ์ดกดลิงก์ไปหน้าบทความได้แยกกัน
 * ตาราง `page_item_widget_slidesetarticle` (PK = `page_item_widget.id`) ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/pageWidget.ts
 */
class SlidesetArticleWidget extends CategoryListWidget
{
    use ReadsArticles;

    public const TYPE = 'slidesetarticle';

    public const IMAGE_FITS = ['cover', 'contain'];

    /** จำนวนการ์ดต่อแถวที่เลือกได้ต่อขนาดหน้าจอ */
    public const PER_ROW_MIN = 1;

    public const PER_ROW_MAX = 6;

    /** จำนวนบรรทัดที่แสดงของหัวเรื่อง/ข้อความเกริ่นนำ (เกินตัดด้วย ...) */
    public const LINES_MIN = 1;

    public const LINES_MAX = 3;

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

    protected function fields(): array
    {
        $perRow = fn (string $label, int $default) => self::number("จำนวนที่แสดงต่อแถว ({$label})", $default, self::PER_ROW_MIN, self::PER_ROW_MAX, ' รายการ');

        return $this->listFields()
            + $this->carouselFields(autoplayDefault: false)
            + [
                'per_row_pc' => $perRow('PC', 4),
                'per_row_notebook' => $perRow('Notebook', 3),
                'per_row_tablet' => $perRow('Tablet', 2),
                'per_row_mobile' => $perRow('Mobile', 1),

                'show_image' => self::flag('การแสดงรูปภาพ', 'Y'),
                'aspect_ratio' => self::choice('อัตราส่วนของรูปภาพ', '16:9', self::ASPECT_RATIOS),
                'image_fit' => self::choice('ประเภทการแสดงรูปภาพ', 'cover', self::IMAGE_FITS),
                'image_clickable' => self::flag('การกดลิงก์ที่รูปภาพ', 'Y'),

                'link_target' => self::choice('เป้าหมายการเปิดลิงก์', '_self', self::LINK_TARGETS),
            ]
            + $this->textFields('title', 'หัวเรื่อง', size: 18, bold: 'Y', color: '#000000', lines: 1, showDefault: 'Y', clickable: 'Y')
            + $this->textFields('intro_text', 'ข้อความเกริ่นนำ', size: 14, bold: 'N', color: '#000000', lines: 2, showDefault: 'Y', clickable: 'N')
            + $this->metaFields('date', 'วันที่เผยแพร่', showDefault: 'Y')
            + $this->metaFields('views', 'จำนวนเข้าชม', showDefault: 'N');
    }

    /**
     * ฟิลด์ของข้อความที่กดลิงก์ได้และกำหนดจำนวนบรรทัดได้ (หัวเรื่อง / ข้อความเกริ่นนำ): แสดง, ขนาด, ตัวหนา, ฟอนต์, สี, จัดตำแหน่ง, กดลิงก์ได้, จำนวนบรรทัด
     * ชื่อคอลัมน์ตาม PageTextStyle (`<part>_font_size` ฯลฯ)
     *
     * @return array<string, array<string, mixed>>
     */
    private function textFields(string $part, string $label, int $size, string $bold, string $color, int $lines, string $showDefault, string $clickable): array
    {
        return [
            $part === 'title' ? 'show_title' : 'show_intro_text' => self::flag("การแสดง{$label}", $showDefault),
            "{$part}_font_size" => self::fontSize("ขนาดตัวอักษรของ{$label}", $size),
            "{$part}_bold" => self::flag("ตัวหนาของ{$label}", $bold),
            "{$part}_font_family" => self::fontFamily("ฟอนต์ของ{$label}"),
            "{$part}_color" => self::color("สีตัวอักษรของ{$label}", $color),
            "{$part}_align" => self::choice("การจัดตำแหน่งของ{$label}", 'left', self::TEXT_ALIGNS),
            "{$part}_clickable" => self::flag("การกดลิงก์ที่{$label}", $clickable),
            "{$part}_lines" => self::number("จำนวนบรรทัดที่แสดงของ{$label}", $lines, self::LINES_MIN, self::LINES_MAX, ' บรรทัด'),
        ];
    }

    /**
     * ฟิลด์ของข้อมูลเสริมของการ์ด (วันที่เผยแพร่ / จำนวนเข้าชม): แสดง, ขนาด, ตัวหนา, ฟอนต์, สี (default เทา)
     *
     * @return array<string, array<string, mixed>>
     */
    private function metaFields(string $part, string $label, string $showDefault): array
    {
        return [
            "show_{$part}" => self::flag("การแสดง{$label}", $showDefault),
            "{$part}_font_size" => self::fontSize("ขนาดตัวอักษรของ{$label}", 12),
            "{$part}_bold" => self::flag("ตัวหนาของ{$label}", 'N'),
            "{$part}_font_family" => self::fontFamily("ฟอนต์ของ{$label}"),
            "{$part}_color" => self::color("สีตัวอักษรของ{$label}", '#667085'),
        ];
    }

    protected function previewQuery(int $categoryId): Builder
    {
        // ไม่บังคับมีรูปหน้าปก (การ์ดแสดงกรอบว่างแทน) — วันที่ที่แสดงใช้วันที่เผยแพร่ (ถ้าไม่มีใช้วันที่สร้าง เหมือนตอนเรียงลำดับ)
        return $this->articleQuery($categoryId, requireImage: false)
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
