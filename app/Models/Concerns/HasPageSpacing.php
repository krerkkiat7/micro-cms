<?php

namespace App\Models\Concerns;

use App\Support\PageSpacing;

/**
 * เพิ่มคอลัมน์ระยะขอบด้านใน/ระยะห่าง (use_padding, padding_*, และ gap_* ของแถว — ดู App\Support\PageSpacing) เข้า $fillable
 * ของแถว/คอลัมน์/widget — model ที่ใช้ต้องประกาศ `SPACING_LEVEL` (row / column / widget)
 */
trait HasPageSpacing
{
    public function initializeHasPageSpacing(): void
    {
        $this->mergeFillable(PageSpacing::columns(static::SPACING_LEVEL));
    }
}
