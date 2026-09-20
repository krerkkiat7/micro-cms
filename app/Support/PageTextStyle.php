<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * การจัดรูปแบบตัวอักษรของ "หัวเรื่อง / หัวเรื่องรอง / ข้อความเกริ่นนำ" ของแถว คอลัมน์ และ widget ในโมดูล Page
 * (ดู docs/PRD-page.md §2) — ทั้ง 3 ข้อความมีชุดตั้งค่าเหมือนกัน 4 ค่า (ขนาด / ฟอนต์ / การจัดตำแหน่ง / สี) เก็บเป็นคอลัมน์
 * จริงในตาราง page_item_row/column/widget ตั้งชื่อ `<ข้อความ>_<ค่า>` เช่น `title_font_size`, `subtitle_align`,
 * `intro_text_color` — คลาสนี้เป็นแหล่งเดียวของรายการคอลัมน์/ค่าที่อนุญาต/ฟอนต์ ให้ model, validation, การบันทึก และหน้าจอใช้ร่วมกัน
 */
class PageTextStyle
{
    /** ข้อความ 3 ส่วนที่จัดรูปแบบได้ (ตรงกับคอลัมน์ในตาราง *_detail: title, subtitle, intro_text) */
    public const PARTS = ['title', 'subtitle', 'intro_text'];

    /** ค่าที่ตั้งได้ของแต่ละข้อความ */
    public const FIELDS = ['font_size', 'font_family', 'align', 'color'];

    public const ALIGNS = ['left', 'center', 'right'];

    public const DEFAULT_FONT = 'Sarabun';

    public const DEFAULT_ALIGN = 'center';

    public const DEFAULT_COLOR = '#000000';

    public const FONT_SIZE_MIN = 8;

    public const FONT_SIZE_MAX = 120;

    /**
     * ฟอนต์ไทยที่นิยมใช้ทำหัวเรื่อง (ฟอนต์ฟรีจาก Google Fonts ที่รองรับภาษาไทย) — ชื่อฟอนต์ => สเปกสำหรับ Bunny Fonts
     * (มิเรอร์ Google Fonts ที่ app.blade.php ใช้อยู่แล้ว): slug:น้ำหนักตัวอักษรที่โหลด — ตัวที่มีน้ำหนักเดียวโหลดแค่ 400
     * ตรวจแล้วว่าทุกรายการโหลดได้จริง ถ้าเพิ่มฟอนต์ใหม่ต้องเช็กว่ามีน้ำหนักที่ระบุจริง ไม่งั้นสไตล์ชีตทั้งชุดจะโหลดไม่ขึ้น
     *
     * @var array<string, string>
     */
    public const FONTS = [
        'Sarabun' => 'sarabun:400,700',
        'Prompt' => 'prompt:400,700',
        'Kanit' => 'kanit:400,700',
        'Noto Sans Thai' => 'noto-sans-thai:400,700',
        'Noto Serif Thai' => 'noto-serif-thai:400,700',
        'IBM Plex Sans Thai' => 'ibm-plex-sans-thai:400,700',
        'Mitr' => 'mitr:400,700',
        'Athiti' => 'athiti:400,700',
        'Bai Jamjuree' => 'bai-jamjuree:400,700',
        'K2D' => 'k2d:400,700',
        'Krub' => 'krub:400,700',
        'Niramit' => 'niramit:400,700',
        'Chakra Petch' => 'chakra-petch:400,700',
        'Anuphan' => 'anuphan:400,700',
        'Pridi' => 'pridi:400,700',
        'Taviraj' => 'taviraj:400,700',
        'Trirong' => 'trirong:400,700',
        'Maitree' => 'maitree:400,700',
        'Mali' => 'mali:400,700',
        'Kodchasan' => 'kodchasan:400,700',
        'KoHo' => 'koho:400,700',
        'Thasadith' => 'thasadith:400,700',
        'Fahkwang' => 'fahkwang:400,700',
        'Srisakdi' => 'srisakdi:400,700',
        'Charmonman' => 'charmonman:400,700',
        'Pattaya' => 'pattaya:400',
        'Itim' => 'itim:400',
        'Sriracha' => 'sriracha:400',
        'Chonburi' => 'chonburi:400',
    ];

    /**
     * ชื่อคอลัมน์ทั้งหมดของการจัดรูปแบบ (3 ข้อความ x 4 ค่า = 12 คอลัมน์)
     *
     * @return list<string>
     */
    public static function columns(): array
    {
        $columns = [];

        foreach (self::PARTS as $part) {
            foreach (self::FIELDS as $field) {
                $columns[] = "{$part}_{$field}";
            }
        }

        return $columns;
    }

    /**
     * ชื่อฟอนต์ทั้งหมด เรียงตามตัวอักษรภาษาอังกฤษ (A-Z ไม่สนตัวพิมพ์เล็ก/ใหญ่) — ใช้เป็นลำดับใน dropdown เลือกฟอนต์
     * และเป็นรายการที่ validation ยอมรับ (ลำดับใน FONTS ไม่มีผลต่อรายการนี้)
     *
     * @return list<string>
     */
    public static function fontNames(): array
    {
        $names = array_keys(self::FONTS);
        sort($names, SORT_STRING | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * URL สไตล์ชีตที่โหลดฟอนต์ทุกตัวในรายการ (ใช้ในหน้าโครงสร้างให้เห็นฟอนต์จริงตอนเลือก และหน้าบ้านในอนาคต)
     */
    public static function fontsStylesheetUrl(): string
    {
        return 'https://fonts.bunny.net/css?family='.implode('|', self::FONTS).'&display=swap';
    }

    /**
     * ดึงเฉพาะคอลัมน์การจัดรูปแบบ (12 ค่า) ออกจากข้อมูลที่ validate แล้วของแถว/คอลัมน์/widget
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fromInput(array $data): array
    {
        return Arr::only($data, self::columns());
    }
}
