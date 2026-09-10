<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

abstract class Controller
{
    /**
     * แปลง input วันที่จาก query string (เช่น Y-m-d) เป็นสตริงวันที่
     * คืน null ถ้าว่างหรือ parse ไม่ได้ — ใช้กับตัวกรองช่วงวันที่ในหน้ารายการ
     */
    protected function toDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
