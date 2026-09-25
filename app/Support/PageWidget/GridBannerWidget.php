<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetGridBanner;
use Illuminate\Database\Eloquent\Builder;

/**
 * widget "Grid จาก banner" — กล่องเรียงต่อเนื่องหลายคอลัมน์ (ไม่เลื่อน) ของ banner ในหมวดหมู่ที่เลือก โครงเดียวกับ GridArticleWidget
 * แต่ตัดส่วนที่ผูกกับบทความออก: **ไม่มี** วันที่เผยแพร่/จำนวนเข้าชม/ปุ่ม "อ่านทั้งหมด" และ**ไม่มี** รูปแบบ "แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ"
 * (banner ไม่มีแนวคิด "วันที่เผยแพร่ที่แสดงต่อผู้ชม" เหมือนบทความ) มีแค่ `card`/`row_image` และเรียงลำดับได้ทั้งวันที่เผยแพร่และลำดับ
 * (`sort_order`) ของ banner เอง (ดู ReadsBanners เหมือน SlideshowBannerWidget/SlidesetBannerWidget) ลิงก์ของการ์ด = ลิงก์ของ banner
 * ตาราง `page_item_widget_gridbanner` (PK = `page_item_widget.id`) ไม่มีตาราง detail เพราะไม่มีฟิลด์แยกภาษา
 */
class GridBannerWidget extends CategoryListWidget
{
    use ReadsBanners;

    public const TYPE = 'gridbanner';

    /** รูปแบบการแสดงผล: การ์ด / แถวที่มีรูปภาพ (banner ไม่มี "แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ") */
    public const DISPLAY_TYPES = ['card', 'row_image'];

    /** จำนวนคอลัมน์ต่อแถวที่เลือกได้ต่อขนาดหน้าจอ */
    public const PER_ROW_MIN = 1;

    public const PER_ROW_MAX = 6;

    /** ความกว้างของพื้นที่รูปภาพเมื่อเป็นรูปแบบ "แถวที่มีรูปภาพ" (% ของความกว้างการ์ด) */
    public const IMAGE_WIDTH_MIN = 5;

    public const IMAGE_WIDTH_MAX = 50;

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'gridBanner';
    }

    protected function model(): string
    {
        return PageItemWidgetGridBanner::class;
    }

    protected function fields(): array
    {
        $perRow = fn (string $label, int $default) => self::number("จำนวนคอลัมน์ที่แสดง ({$label})", $default, self::PER_ROW_MIN, self::PER_ROW_MAX, ' คอลัมน์');

        return $this->listFields()
            + [
                'display_type' => self::choice('รูปแบบการแสดงผล', 'card', self::DISPLAY_TYPES),

                'per_row_pc' => $perRow('PC', 4),
                'per_row_notebook' => $perRow('Notebook', 3),
                'per_row_tablet' => $perRow('Tablet', 2),
                'per_row_mobile' => $perRow('Mobile', 1),

                'link_target' => self::choice('เป้าหมายการเปิดลิงก์', '_self', self::LINK_TARGETS),

                'show_image' => self::flag('การแสดงรูปภาพ', 'Y'),
                'image_width_percent' => self::number('ความกว้างของพื้นที่แสดงรูปภาพ', 20, self::IMAGE_WIDTH_MIN, self::IMAGE_WIDTH_MAX, '%'),
                'aspect_ratio' => self::choice('อัตราส่วนของรูปภาพ', '16:9', self::ASPECT_RATIOS),
                'image_fit' => self::choice('ประเภทการแสดงรูปภาพ', 'cover', self::IMAGE_FITS),
                'image_background' => self::backgroundColor('สีพื้นหลังของรูปภาพ', self::DEFAULT_IMAGE_BACKGROUND),
                'image_clickable' => self::flag('การกดลิงก์ที่รูปภาพ', 'Y'),
            ]
            + $this->cardBoxFields()
            + $this->textFields('title', 'หัวเรื่อง', size: 18, bold: 'Y', color: '#000000', lines: 1, showDefault: 'Y', clickable: 'Y')
            + $this->textFields('intro_text', 'ข้อความเกริ่นนำ', size: 14, bold: 'N', color: '#000000', lines: 2, showDefault: 'N', clickable: 'N');
    }

    protected function normalize(array $values): array
    {
        $values = parent::normalize($values);

        // แถว (row_image) บังคับแสดงหัวเรื่องเสมอ เหมือน Grid จาก article (ปิดได้เฉพาะรูปแบบการ์ด)
        if (($values['display_type'] ?? 'card') !== 'card') {
            $values['show_title'] = 'Y';
        }

        return $values;
    }

    protected function previewQuery(int $categoryId, ?string $lang = null): Builder
    {
        return $this->bannerQuery($categoryId, $lang);
    }

    protected function previewRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'image' => $row->image,
            'title' => $row->title ?? '',
            'intro_text' => $row->intro_text ?? '',
            // ไม่ส่ง URL — ตัวอย่างแค่บอกว่ามีลิงก์ (กดไม่ได้)
            'has_link' => (bool) $row->has_link,
        ];
    }
}
