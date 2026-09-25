<?php

namespace App\Support\PageWidget;

/**
 * ส่วนที่ใช้ร่วมกันของ widget กลุ่ม Slideshow (ภาพเต็มภาพเดียวที่สไลด์ได้) — การตั้งค่าเหมือนกันทุกแหล่งข้อมูล
 * (ลูกศร จุด เลื่อนอัตโนมัติ effect สัดส่วนภาพ ลิงก์ ข้อความบนภาพ + ตัวอักษรของหัวเรื่อง/ข้อความเกริ่นนำ ฯลฯ) ต่างกันที่แหล่งข้อมูล:
 * คลาสลูกกำหนดตารางตั้งค่า/คอลัมน์หมวดหมู่/ตัวเลือกการเรียงลำดับและวิธีดึงข้อมูลตัวอย่าง (ตอนนี้มี banner และ article)
 * ตัวเลือก/ค่าเริ่มต้นต้องตรงกับ utils/pageWidget.ts
 */
abstract class SlideshowWidget extends CategoryListWidget
{
    public const EFFECTS = ['slide', 'fade', 'zoom'];

    public const TEXT_WIDTHS = ['full', 'container'];

    /** ตำแหน่งข้อความบนภาพ 9 ตำแหน่ง (ค่าแบบ CSS background-position — ชุดเดียวกับ BACKGROUND_POSITION_STYLES ฝั่ง Vue ไม่รวม "ไม่ระบุ") */
    public const TEXT_POSITIONS = [
        'top left', 'top', 'top right',
        'left', 'center', 'right',
        'bottom left', 'bottom', 'bottom right',
    ];

    protected function fields(): array
    {
        return $this->listFields() + $this->carouselFields() + [
            'transition_effect' => self::choice('ประเภทการเลื่อน', 'slide', self::EFFECTS),
            'aspect_ratio' => self::choice('สัดส่วนภาพ', '16:9', self::ASPECT_RATIOS),
            'is_clickable' => self::flag('การกดลิงก์', 'Y'),
            'link_target' => self::choice('เป้าหมายการเปิดลิงก์', '_self', self::LINK_TARGETS),
            'show_title' => self::flag('การแสดงหัวเรื่อง', 'Y'),
            'show_intro_text' => self::flag('การแสดงข้อความเกริ่นนำ', 'N'),
            'text_align' => self::choice('ตำแหน่งที่แสดงข้อความ', 'center', self::TEXT_POSITIONS),
            'text_width' => self::choice('ขอบเขตของข้อความ', 'container', self::TEXT_WIDTHS),
            // ข้อความบนภาพ: ขนาด/ฟอนต์/สี (default ขาว เพราะซ้อนบนภาพ)/ตัวหนา
            'title_font_size' => self::fontSize('ขนาดตัวอักษรของหัวเรื่อง', 20),
            'title_font_family' => self::fontFamily('ฟอนต์ของหัวเรื่อง'),
            'title_color' => self::color('สีตัวอักษรของหัวเรื่อง', '#FFFFFF'),
            'title_bold' => self::flag('ตัวหนาของหัวเรื่อง', 'Y'),
            'intro_text_font_size' => self::fontSize('ขนาดตัวอักษรของข้อความเกริ่นนำ', 16),
            'intro_text_font_family' => self::fontFamily('ฟอนต์ของข้อความเกริ่นนำ'),
            'intro_text_color' => self::color('สีตัวอักษรของข้อความเกริ่นนำ', '#FFFFFF'),
            'intro_text_bold' => self::flag('ตัวหนาของข้อความเกริ่นนำ', 'N'),
        ];
    }

    protected function frontImageWidth(): int
    {
        return 1920;
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
