<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * ระยะขอบด้านใน (padding) ของแถว/คอลัมน์/widget และระยะห่างระหว่างคอลัมน์ (gap) ของแถว ในโมดูล Page (ดู docs/PRD-page.md §2)
 * — padding มีสวิตช์เปิด/ปิด `use_padding` (ปิด = ไม่เว้นระยะ แม้จะมีตัวเลขเก็บไว้) + ตัวเลข 4 ด้านหน่วย px, gap มีเฉพาะแถว
 * (ใช้กับ grid ของคอลัมน์ข้างใน) คลาสนี้เป็นแหล่งเดียวของรายการคอลัมน์/ค่าเริ่มต้น/ช่วงค่าที่อนุญาต ให้ migration, model,
 * validation, การบันทึก และการอ่านไปแสดงที่หน้าบ้านใช้ร่วมกัน (ค่าเริ่มต้นต้องตรงกับ resources/js/utils/pageLayout.ts)
 */
class PageSpacing
{
    public const LEVELS = ['row', 'column', 'widget'];

    public const SIDES = ['top', 'right', 'bottom', 'left'];

    public const PADDING_MIN = 0;

    public const PADDING_MAX = 200;

    public const GAP_MIN = 0;

    public const GAP_MAX = 120;

    /** ระยะห่างระหว่างคอลัมน์เริ่มต้น (px) — 24px = 1.5rem ระยะ gutter ที่เว็บส่วนใหญ่ใช้ (เช่น Bootstrap) */
    public const DEFAULT_GAP = 24;

    /** padding เริ่มต้นเมื่อเปิดใช้งาน [บน, ขวา, ล่าง, ซ้าย] (px) ของแต่ละชั้น */
    public const DEFAULT_PADDING = [
        'row' => [48, 16, 48, 16],
        'column' => [16, 16, 16, 16],
        'widget' => [16, 16, 16, 16],
    ];

    /**
     * ชื่อคอลัมน์ทั้งหมดของชั้นที่ระบุ (แถวมี gap_x/gap_y เพิ่ม)
     *
     * @return list<string>
     */
    public static function columns(string $level): array
    {
        $columns = ['use_padding'];

        foreach (self::SIDES as $side) {
            $columns[] = "padding_{$side}";
        }

        if ($level === 'row') {
            $columns[] = 'gap_x';
            $columns[] = 'gap_y';
        }

        return $columns;
    }

    /**
     * ดึงเฉพาะคอลัมน์ระยะขอบ/ระยะห่างออกจากข้อมูลที่ validate แล้วของแถว/คอลัมน์/widget
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fromInput(array $data, string $level): array
    {
        return Arr::only($data, self::columns($level));
    }

    /**
     * padding สำหรับแสดงผลที่หน้าบ้าน — ปิดใช้งาน = null
     *
     * @return array{top: int, right: int, bottom: int, left: int}|null
     */
    public static function padding(object $model): ?array
    {
        if (($model->use_padding ?? 'N') !== 'Y') {
            return null;
        }

        $padding = [];

        foreach (self::SIDES as $side) {
            $padding[$side] = (int) $model->{"padding_{$side}"};
        }

        return $padding;
    }
}
