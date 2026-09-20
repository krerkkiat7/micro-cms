<?php

namespace App\Models\Concerns;

use App\Support\PageTextStyle;

/**
 * เพิ่มคอลัมน์การจัดรูปแบบตัวอักษร (title_*, subtitle_*, intro_text_* — ดู App\Support\PageTextStyle) เข้า $fillable
 * ของแถว/คอลัมน์/widget โดยไม่ต้องเขียนซ้ำ 12 ชื่อในทุก model (Laravel เรียก initialize<ชื่อ trait>() ตอนสร้าง model)
 */
trait HasPageTextStyle
{
    public function initializeHasPageTextStyle(): void
    {
        $this->mergeFillable(PageTextStyle::columns());
    }
}
