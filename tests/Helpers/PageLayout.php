<?php

// helper สร้าง payload โครงสร้างหน้าเพจ (แถว → คอลัมน์ → widget) สำหรับเทสของโมดูล Page — โหลดจาก tests/Pest.php

/**
 * ค่าการจัดรูปแบบตัวอักษรครบ 15 ค่า (หัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ) ที่หน้าจอส่งมาเสมอ
 *
 * @return array<string, mixed>
 */
function layoutTextStyle(int $titleSize = 32, array $overrides = []): array
{
    $style = [];

    foreach (['title' => $titleSize, 'subtitle' => 20, 'intro_text' => 16] as $part => $size) {
        $style["{$part}_font_size"] = $size;
        $style["{$part}_font_family"] = 'Sarabun';
        $style["{$part}_align"] = 'center';
        $style["{$part}_color"] = '#000000';
        $style["{$part}_bold"] = $part === 'title' ? 'Y' : 'N';
    }

    return array_replace($style, $overrides);
}

/**
 * ค่าระยะขอบด้านใน (+ ระยะห่างระหว่างคอลัมน์ถ้าเป็นแถว) ที่หน้าจอส่งมาเสมอ — ค่าเริ่มต้น = ปิด padding ตามค่า default ของ migration
 *
 * @return array<string, mixed>
 */
function layoutSpacing(string $level, array $overrides = []): array
{
    $padding = $level === 'row' ? [48, 16, 48, 16] : [16, 16, 16, 16];
    $spacing = ['use_padding' => 'N'];

    foreach (['top', 'right', 'bottom', 'left'] as $index => $side) {
        $spacing["padding_{$side}"] = $padding[$index];
    }

    if ($level === 'row') {
        $spacing += ['gap_x' => 24, 'gap_y' => 24];
    }

    return array_replace($spacing, $overrides);
}

/**
 * @return array<string, mixed>
 */
function layoutWidget(array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'show_title' => 'Y',
        'widget_type' => 'placeholder',
        'setting' => [],
        'background_color' => 'transparent',
        'detail' => ['th' => ['title' => 'วิดเจ็ต', 'subtitle' => '', 'intro_text' => ''], 'en' => ['title' => 'Widget', 'subtitle' => '', 'intro_text' => '']],
    ] + layoutTextStyle(20) + layoutSpacing('widget'), $overrides);
}

/**
 * @param  list<array<string, mixed>>  $widgets
 * @return array<string, mixed>
 */
function layoutColumn(array $widgets = [], array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'show_title' => 'N',
        'column_size' => 12,
        'background_color' => 'transparent',
        'detail' => ['th' => ['title' => 'คอลัมน์', 'subtitle' => '', 'intro_text' => ''], 'en' => ['title' => 'Column', 'subtitle' => '', 'intro_text' => '']],
        'widgets' => $widgets,
    ] + layoutTextStyle(24) + layoutSpacing('column'), $overrides);
}

/**
 * @param  list<array<string, mixed>>  $columns
 * @return array<string, mixed>
 */
function layoutRow(array $columns = [], array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'show_title' => 'N',
        'use_container' => 'Y',
        'background_color' => 'transparent',
        'detail' => ['th' => ['title' => 'แถว', 'subtitle' => '', 'intro_text' => ''], 'en' => ['title' => 'Row', 'subtitle' => '', 'intro_text' => '']],
        'columns' => $columns,
    ] + layoutTextStyle(32) + layoutSpacing('row'), $overrides);
}
