<?php

namespace App\Support\PageWidget;

/**
 * ส่วนที่ใช้ร่วมกันของ widget กลุ่ม Slideset (การ์ดหลายใบที่เลื่อนดูได้) — จำนวนการ์ดต่อแถวตามขนาดหน้าจอ (PC / Notebook / Tablet / Mobile),
 * กล่องการ์ด (เส้นขอบ, มุมมน), รูปภาพ (อัตราส่วน, cover/contain + สีพื้นหลังเมื่อ contain, กดลิงก์ได้), หัวเรื่อง และข้อความเกริ่นนำ (แสดง/ขนาด/ตัวหนา/ฟอนต์/สี/จัดตำแหน่ง/กดลิงก์ได้/
 * จำนวนบรรทัดที่ตัดด้วย ...) และเป้าหมายการเปิดลิงก์ที่ใช้ร่วมกัน คลาสลูกกำหนดแหล่งข้อมูล (article / banner) ค่าเริ่มต้นของ "แสดงข้อความเกริ่นนำ"
 * และฟิลด์เพิ่มเติมของตัวเอง (`extraFields()` เช่น วันที่/จำนวนเข้าชม/ปุ่มอ่านทั้งหมดของ article) เลื่อนทีละ "หน้า" (ครั้งละเท่าจำนวนต่อแถว)
 * ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/pageWidget.ts
 */
abstract class SlidesetWidget extends CategoryListWidget
{
    public const IMAGE_FITS = ['cover', 'contain'];

    /** จำนวนการ์ดต่อแถวที่เลือกได้ต่อขนาดหน้าจอ */
    public const PER_ROW_MIN = 1;

    public const PER_ROW_MAX = 6;

    /** จำนวนบรรทัดที่แสดงของหัวเรื่อง/ข้อความเกริ่นนำ (เกินตัดด้วย ...) */
    public const LINES_MIN = 1;

    public const LINES_MAX = 3;

    /** สีพื้นหลังเริ่มต้นของกรอบรูปเมื่อแสดงแบบ contain (เทาอ่อน = bg-gray-100 เหมือนกรอบรูปในหน้าจัดการไฟล์) */
    public const DEFAULT_IMAGE_BACKGROUND = '#F3F4F6';

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

                // กล่องของการ์ด: เส้นขอบ / มุมมน (default มีเส้นขอบและมุมมน)
                'show_border' => self::flag('การแสดงเส้นขอบของกล่อง', 'Y'),
                'rounded_corners' => self::flag('การทำมุมมนของกล่อง', 'Y'),
            ]
            + $this->textFields('title', 'หัวเรื่อง', size: 18, bold: 'Y', color: '#000000', lines: 1, showDefault: 'Y', clickable: 'Y')
            + $this->textFields('intro_text', 'ข้อความเกริ่นนำ', size: 14, bold: 'N', color: '#000000', lines: 2, showDefault: $this->introShownByDefault(), clickable: 'N')
            + $this->extraFields();
    }

    /**
     * ฟิลด์ของข้อความที่กดลิงก์ได้และกำหนดจำนวนบรรทัดได้ (หัวเรื่อง / ข้อความเกริ่นนำ): แสดง, ขนาด, ตัวหนา, ฟอนต์, สี, จัดตำแหน่ง, กดลิงก์ได้, จำนวนบรรทัด
     * ชื่อคอลัมน์ตาม PageTextStyle (`<part>_font_size` ฯลฯ)
     *
     * @return array<string, array<string, mixed>>
     */
    protected function textFields(string $part, string $label, int $size, string $bold, string $color, int $lines, string $showDefault, string $clickable): array
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
     * ฟิลด์ของข้อมูลเสริมของการ์ด (เช่น วันที่เผยแพร่ / จำนวนเข้าชม): แสดง, ขนาด, ตัวหนา, ฟอนต์, สี (default เทา)
     *
     * @return array<string, array<string, mixed>>
     */
    protected function metaFields(string $part, string $label, string $showDefault): array
    {
        return [
            "show_{$part}" => self::flag("การแสดง{$label}", $showDefault),
            "{$part}_font_size" => self::fontSize("ขนาดตัวอักษรของ{$label}", 12),
            "{$part}_bold" => self::flag("ตัวหนาของ{$label}", 'N'),
            "{$part}_font_family" => self::fontFamily("ฟอนต์ของ{$label}"),
            "{$part}_color" => self::color("สีตัวอักษรของ{$label}", '#667085'),
        ];
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
