<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    /**
     * ชื่อรายการในหน้ารายการหลังบ้าน — ใช้ของภาษาหลัก (alias `d` จาก leftJoin) ก่อน ไม่มีค่อย fallback ไปภาษาอื่นที่มีข้อมูล
     * (เช่น ภาษาหลักเพิ่งถูกเปลี่ยนเป็นภาษาใหม่ที่รายการเดิมยังไม่ได้กรอก) — ให้รายการไม่หายไปจากหน้ารายการ
     * $detailTable/$infoTable/$column ต้องเป็นค่าคงที่จากโค้ด (ไม่ใช่ input ผู้ใช้)
     */
    protected function detailWithFallback(string $detailTable, string $infoTable, string $column): Expression
    {
        return DB::raw(
            "coalesce(d.{$column}, (select f.{$column} from {$detailTable} f where f.id = {$infoTable}.id and f.{$column} is not null and f.deleted_at is null "
            ."order by f.lang limit 1)) as {$column}"
        );
    }
}
