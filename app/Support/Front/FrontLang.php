<?php

namespace App\Support\Front;

use App\Support\Setting;
use Illuminate\Database\Query\JoinClause;

/**
 * ตัวช่วยเลือกข้อมูลแยกภาษา (ตาราง *_detail) ของหน้าบ้าน — ภาษาที่ขอยังไม่ได้แปล (หัวเรื่องว่าง) ใช้ข้อมูลภาษาหลักแทน
 * (หลังบ้านบังคับกรอกเฉพาะภาษาหลัก ภาษาอื่นเว้นว่างได้)
 */
final class FrontLang
{
    /**
     * เงื่อนไข join ตาราง detail: `{alias}.lang` = ภาษาที่ขอ ถ้าแถวของภาษานั้นมีหัวเรื่อง ไม่งั้นภาษาหลัก
     *
     * @param  string  $detailTable  ชื่อตาราง detail จริง (ใช้ใน subquery)
     * @param  string  $infoColumn  คอลัมน์ id ของตาราง info เช่น `article_item_info.id`
     */
    public static function joinDetail(JoinClause $join, string $alias, string $detailTable, string $infoColumn, ?string $lang = null, string $titleColumn = 'title'): JoinClause
    {
        $default = Setting::defaultLanguage();
        $lang ??= $default;

        $join->on("{$alias}.id", '=', $infoColumn);

        if ($lang === $default) {
            return $join->where("{$alias}.lang", '=', $default);
        }

        return $join->whereRaw(
            "{$alias}.lang = (case when exists (select 1 from {$detailTable} as fl where fl.id = {$infoColumn} and fl.lang = ? and fl.{$titleColumn} is not null and fl.{$titleColumn} <> '') then ? else ? end)",
            [$lang, $lang, $default],
        );
    }

    /**
     * เลือกแถว detail ที่ใช้แสดงจาก collection ของทุกภาษา (ภาษาที่ขอถ้ามีหัวเรื่อง ไม่งั้นภาษาหลัก)
     *
     * @param  iterable<object>  $details
     */
    public static function pick(iterable $details, string $lang, string $titleColumn = 'title'): ?object
    {
        $byLang = collect($details)->keyBy('lang');
        $requested = $byLang->get($lang);

        if ($requested && trim((string) $requested->{$titleColumn}) !== '') {
            return $requested;
        }

        return $byLang->get(Setting::defaultLanguage()) ?? $requested;
    }
}
