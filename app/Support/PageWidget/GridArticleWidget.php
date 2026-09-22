<?php

namespace App\Support\PageWidget;

use App\Models\PageItemWidgetGridArticle;
use App\Models\PageItemWidgetGridArticleDetail;
use Illuminate\Database\Eloquent\Builder;

/**
 * widget "Grid จาก article" — กล่องเรียงต่อเนื่องหลายคอลัมน์ (ไม่เลื่อน ต่างจาก Slideset) ของบทความในหมวดหมู่ที่เลือก มี 3 รูปแบบการแสดงผล
 * (`display_type`): `card` (การ์ด — ทุกส่วนเปิด/ปิดเองได้), `row_image` (แถวแบ่ง 2 ส่วน ซ้าย=รูปกว้างเป็น % ขวา=ข้อมูล — หัวเรื่องบังคับแสดงเสมอ),
 * `row_date` (แถวแบ่ง 2 ส่วน ซ้าย=กล่องวันที่ (วันที่ + เดือนย่อปี) แทนรูป ขวา=ข้อมูล — หัวเรื่องและวันที่บังคับแสดงเสมอ) จำนวนคอลัมน์ต่อแถวตามขนาดหน้าจอ
 * ใช้ร่วมกันทุกรูปแบบ ส่วนของการ์ด (รูป/หัวเรื่อง/ข้อความเกริ่นนำ/วันที่/จำนวนเข้าชม) ใช้ฟิลด์ชุดเดียวกับ SlidesetWidget (ผ่าน CategoryListWidget::textFields/metaFields)
 * และมีปุ่ม "อ่านทั้งหมด" เหมือน Slideset จาก article (ดู HasReadAllButton) ตาราง `page_item_widget_gridarticle` (+ `_detail`) PK = `page_item_widget.id`
 */
class GridArticleWidget extends CategoryListWidget
{
    use HasReadAllButton;
    use ReadsArticles;

    public const TYPE = 'gridarticle';

    /** รูปแบบการแสดงผล: การ์ด / แถวที่มีรูปภาพ / แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ */
    public const DISPLAY_TYPES = ['card', 'row_image', 'row_date'];

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
        return 'gridArticle';
    }

    protected function model(): string
    {
        return PageItemWidgetGridArticle::class;
    }

    protected function detailModel(): ?string
    {
        return PageItemWidgetGridArticleDetail::class;
    }

    protected function detailFields(): array
    {
        return $this->readAllDetailFields();
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

                // รูปภาพ (ใช้กับรูปแบบการ์ด / แถวที่มีรูปภาพ — แถวที่แสดงวันที่แทนรูปภาพไม่มีส่วนนี้)
                'show_image' => self::flag('การแสดงรูปภาพ', 'Y'),
                // ความกว้างของพื้นที่รูปภาพ (%) — ใช้เฉพาะรูปแบบ "แถวที่มีรูปภาพ"
                'image_width_percent' => self::number('ความกว้างของพื้นที่แสดงรูปภาพ', 20, self::IMAGE_WIDTH_MIN, self::IMAGE_WIDTH_MAX, '%'),
                'aspect_ratio' => self::choice('อัตราส่วนของรูปภาพ', '16:9', self::ASPECT_RATIOS),
                'image_fit' => self::choice('ประเภทการแสดงรูปภาพ', 'cover', self::IMAGE_FITS),
                'image_background' => self::backgroundColor('สีพื้นหลังของรูปภาพ', self::DEFAULT_IMAGE_BACKGROUND),
                'image_clickable' => self::flag('การกดลิงก์ที่รูปภาพ', 'Y'),
            ]
            + $this->textFields('title', 'หัวเรื่อง', size: 18, bold: 'Y', color: '#000000', lines: 1, showDefault: 'Y', clickable: 'Y')
            + $this->textFields('intro_text', 'ข้อความเกริ่นนำ', size: 14, bold: 'N', color: '#000000', lines: 2, showDefault: 'N', clickable: 'N')
            + $this->metaFields('date', 'วันที่เผยแพร่', showDefault: 'Y')
            + $this->metaFields('views', 'จำนวนเข้าชม', showDefault: 'N')
            + $this->readAllFields();
    }

    protected function normalize(array $values): array
    {
        $values = $this->normalizeReadAllUrl(parent::normalize($values));
        $displayType = $values['display_type'] ?? 'card';

        // แถวทั้ง 2 รูปแบบบังคับแสดงหัวเรื่องเสมอ (ปิดได้เฉพาะรูปแบบการ์ด)
        if ($displayType !== 'card') {
            $values['show_title'] = 'Y';
        }

        // แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ บังคับแสดงวันที่เสมอ (คือส่วนที่แทนที่รูป)
        if ($displayType === 'row_date') {
            $values['show_date'] = 'Y';
        }

        return $values;
    }

    protected function previewQuery(int $categoryId): Builder
    {
        // ไม่บังคับมีรูปหน้าปก (รูปแบบการ์ด/แถวที่มีรูปภาพแสดงกรอบว่างแทน) — วันที่ที่แสดงใช้วันที่เผยแพร่ (ไม่มีใช้วันที่สร้าง เหมือนตอนเรียงลำดับ)
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
