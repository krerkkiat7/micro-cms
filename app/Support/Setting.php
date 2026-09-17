<?php

namespace App\Support;

use App\Models\SysSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * อ่านค่าตั้งค่าระบบ (sys_setting) แบบ cache-first — โหลดทั้งกลุ่มแล้วแคชไว้ (invalidate ตอนบันทึก)
 * ตามที่ CLAUDE.md ระบุไว้ว่าจะทำ (ดู docs/PRD-system.md)
 *
 * GROUPS คือทะเบียนกลุ่มตั้งค่าทั้งหมดที่มี — ใช้ทั้งวนสร้างฟอร์ม/ปุ่มล้างแคช และ "ล้างแคชทั้งหมด"
 * เพิ่มกลุ่มตั้งค่าใหม่ในอนาคต ให้เพิ่มชื่อกลุ่มในนี้ด้วย
 */
class Setting
{
    public const GROUPS = ['site', 'smtp', 'turnstile', 'login_back', 'article'];

    /**
     * ค่าตั้งค่าทั้งกลุ่ม เป็น array แบบ name => value — แคชไว้ 1 วัน
     * คืน [] เงียบ ๆ ถ้าตาราง sys_setting ยังไม่มี (เช่น ก่อนรัน migrate ครั้งแรก)
     */
    public static function group(string $group): array
    {
        return Cache::remember("sys_setting.{$group}", now()->addDay(), function () use ($group) {
            if (! Schema::hasTable('sys_setting')) {
                return [];
            }

            return SysSetting::query()
                ->where('group', $group)
                ->pluck('value', 'name')
                ->all();
        });
    }

    /**
     * ค่าตั้งค่า 1 ตัวในกลุ่ม — คืน $default ถ้าไม่มีค่า (ไม่เคยตั้ง หรือค่าว่าง)
     */
    public static function get(string $group, string $name, mixed $default = null): mixed
    {
        $value = self::group($group)[$name] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    /**
     * ล้างแคชของกลุ่มเดียว — เรียกทันทีหลังบันทึกค่ากลุ่มนั้น
     */
    public static function forget(string $group): void
    {
        Cache::forget("sys_setting.{$group}");
    }

    /**
     * ล้างแคชของทุกกลุ่มที่ลงทะเบียนไว้ (GROUPS)
     */
    public static function forgetAll(): void
    {
        foreach (self::GROUPS as $group) {
            self::forget($group);
        }
    }

    /**
     * ชื่อไซต์สำหรับแสดงผล (เช่น <title>) — ใช้ค่าจากการตั้งค่าก่อน ไม่มีค่อย fallback ไปที่ .env (APP_NAME)
     */
    public static function siteName(): string
    {
        return self::get('site', 'site_name') ?? config('app.name', 'Laravel');
    }

    /**
     * รหัสภาษาที่เปิดใช้ในระบบ (sys_setting: site.lang_selected) — เก็บรวมเป็น string เดียวคั่นด้วย ,
     * (ไม่แยกเก็บทีละภาษา) เพื่อให้เพิ่มภาษาในอนาคตได้โดยไม่ต้องแก้ schema — ยังไม่ได้ตั้งค่า fallback เป็น
     * th,en เพื่อให้ตรงกับพฤติกรรมเดิมของระบบ (ก่อนมีฟีเจอร์นี้)
     *
     * ใช้กำหนดว่า route ส่วน {lang} (routes/web.php) จะรับค่าอะไรได้บ้าง
     *
     * @return list<string>
     */
    public static function selectedLanguages(): array
    {
        return array_values(array_filter(explode(',', (string) self::get('site', 'lang_selected', 'th,en'))));
    }

    /**
     * รหัสภาษาหลักของระบบ (sys_setting: site.lang_default) — ยังไม่ได้ตั้งค่า หรือค่าที่ตั้งไว้ไม่อยู่ใน
     * selectedLanguages() อีกแล้ว (เช่น แก้ข้อมูลตรง ๆ ใน DB) fallback ไปที่ภาษาแรกใน selectedLanguages() เสมอ
     * — การันตีว่าค่าที่คืนไปใช้ redirect ('/') จะตรงกับ route {lang} ที่อนุญาตไว้จริงเสมอ
     */
    public static function defaultLanguage(): string
    {
        $selected = self::selectedLanguages();
        $default = self::get('site', 'lang_default');

        return $default !== null && in_array($default, $selected, true) ? $default : ($selected[0] ?? 'th');
    }

    /**
     * regex สำหรับ route `where('lang', ...)` (routes/web.php) — สร้างจาก selectedLanguages() เสมอ
     * เพื่อให้ URL /{lang}/... รับเฉพาะภาษาที่เปิดใช้งานจริงในตั้งค่าระบบ (ไม่ hardcode th|en อีกต่อไป)
     */
    public static function languageRoutePattern(): string
    {
        $selected = self::selectedLanguages();

        return implode('|', array_map('preg_quote', $selected !== [] ? $selected : ['th', 'en']));
    }
}
