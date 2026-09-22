<?php

namespace App\Support\PageWidget;

/**
 * ส่วนที่ใช้ร่วมกันของ widget กลุ่ม Slideset (การ์ดหลายใบที่เลื่อนดูได้) — จำนวนการ์ดต่อแถวตามขนาดหน้าจอ (PC / Notebook / Tablet / Mobile),
 * กล่องการ์ด (เส้นขอบ+สีเส้นขอบ, มุมมน, สีพื้นหลังของแต่ละรายการ — `cardBoxFields()` ใน CategoryListWidget ใช้ร่วมกับ Grid), รูปภาพ (อัตราส่วน, cover/contain + สีพื้นหลังเมื่อ contain, กดลิงก์ได้), หัวเรื่อง และข้อความเกริ่นนำ (แสดง/ขนาด/ตัวหนา/ฟอนต์/สี/จัดตำแหน่ง/กดลิงก์ได้/
 * จำนวนบรรทัดที่ตัดด้วย ...) และเป้าหมายการเปิดลิงก์ที่ใช้ร่วมกัน คลาสลูกกำหนดแหล่งข้อมูล (article / banner) ค่าเริ่มต้นของ "แสดงข้อความเกริ่นนำ"
 * และฟิลด์เพิ่มเติมของตัวเอง (`extraFields()` เช่น วันที่/จำนวนเข้าชม/ปุ่มอ่านทั้งหมดของ article) เลื่อนทีละ "หน้า" (ครั้งละเท่าจำนวนต่อแถว)
 * ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/pageWidget.ts
 */
abstract class SlidesetWidget extends CategoryListWidget
{
    /** จำนวนการ์ดต่อแถวที่เลือกได้ต่อขนาดหน้าจอ */
    public const PER_ROW_MIN = 1;

    public const PER_ROW_MAX = 6;

    /** ค่าเริ่มต้นของ "แสดงข้อความเกริ่นนำ" ('Y' / 'N') */
    abstract protected function introShownByDefault(): string;

    /**
     * ฟิลด์เพิ่มเติมเฉพาะแหล่งข้อมูล
     *
     * @return array<string, array<string, mixed>>
     */
    protected function extraFields(): array
    {
        return [];
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
                'image_background' => self::backgroundColor('สีพื้นหลังของรูปภาพ', self::DEFAULT_IMAGE_BACKGROUND),
                'image_clickable' => self::flag('การกดลิงก์ที่รูปภาพ', 'Y'),

                'link_target' => self::choice('เป้าหมายการเปิดลิงก์', '_self', self::LINK_TARGETS),
            ]
            + $this->cardBoxFields()
            + $this->textFields('title', 'หัวเรื่อง', size: 18, bold: 'Y', color: '#000000', lines: 1, showDefault: 'Y', clickable: 'Y')
            + $this->textFields('intro_text', 'ข้อความเกริ่นนำ', size: 14, bold: 'N', color: '#000000', lines: 2, showDefault: $this->introShownByDefault(), clickable: 'N')
            + $this->extraFields();
    }

    protected function previewRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'image' => $row->image, // null = ไม่มีรูป (เฉพาะแหล่งข้อมูลที่ไม่บังคับรูป)
            'title' => $row->title ?? '',
            'intro_text' => $row->intro_text ?? '',
            // ไม่ส่ง URL — ตัวอย่างแค่บอกว่ามีลิงก์ (กดไม่ได้)
            'has_link' => (bool) $row->has_link,
        ];
    }
}
