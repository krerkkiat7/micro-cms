<?php

namespace App\Support;

use App\Models\SysSetting;
use App\Support\Front\FrontCache;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use PDOException;

/**
 * อ่านค่าตั้งค่าระบบ (sys_setting) แบบ cache-first — โหลดทั้งกลุ่มแล้วแคชไว้ (invalidate ตอนบันทึก)
 * ตามที่ CLAUDE.md ระบุไว้ว่าจะทำ (ดู docs/PRD-system.md)
 *
 * GROUPS คือทะเบียนกลุ่มตั้งค่าทั้งหมดที่มี — ใช้ทั้งวนสร้างฟอร์ม/ปุ่มล้างแคช และ "ล้างแคชทั้งหมด"
 * เพิ่มกลุ่มตั้งค่าใหม่ในอนาคต ให้เพิ่มชื่อกลุ่มในนี้ด้วย
 */
class Setting
{
    public const GROUPS = ['site', 'contact', 'social', 'google_analytics', 'google_map', 'smtp', 'turnstile', 'login_back', 'article', 'banner', 'popup', 'contactus'];

    /**
     * ค่าลับ (กลุ่ม => ชื่อฟิลด์) — เก็บใน sys_setting แบบเข้ารหัสด้วย APP_KEY (Crypt = AES-256-CBC + HMAC) และใน cache ก็เป็นค่าที่เข้ารหัส
     * เข้ารหัสอัตโนมัติตอนบันทึก (SysSetting::saving) / ถอดรหัสตอนอ่านผ่าน group()/get() — โค้ดที่ใช้ค่าไม่ต้องรู้เรื่องการเข้ารหัส
     * **เปลี่ยน APP_KEY = ถอดรหัสค่าเดิมไม่ได้** (อ่านได้เป็น null) ต้องกรอกค่าลับใหม่ที่หน้าตั้งค่าระบบ
     */
    public const SECRETS = ['smtp' => ['password'], 'turnstile' => ['key_secret']];

    /**
     * ค่าตั้งค่าทั้งกลุ่ม เป็น array แบบ name => value — แคชไว้ 1 วัน
     * ผ่าน Cache::memo() — อ่านจาก cache store จริงครั้งเดียวต่อ request (หน้าบ้าน 1 หน้าเรียก Setting หลายสิบครั้ง)
     * คืน [] เงียบ ๆ (และไม่ cache) ถ้ายังอ่านตาราง sys_setting ไม่ได้ (เช่น ก่อนรัน migrate ครั้งแรก)
     */
    public static function group(string $group): array
    {
        $cache = Cache::memo();
        $key = "sys_setting.{$group}";

        // try ครอบทั้งการอ่าน cache และ DB — ติดตั้งใหม่ยังไม่ได้ migrate (ไม่มีตาราง sys_setting และตาราง cache เมื่อ
        // CACHE_STORE=database) หรือเชื่อมต่อ DB ไม่ได้ → คืนค่าว่างโดยไม่ cache (AppServiceProvider เรียกทุก request/คำสั่ง artisan)
        try {
            $values = $cache->get($key);

            if (! is_array($values)) {
                $values = SysSetting::query()->where('group', $group)->pluck('value', 'name')->all();
                $cache->put($key, $values, now()->addDay());
            }

            return self::revealSecrets($group, $values);
        } catch (QueryException|PDOException) {
            return [];
        }
    }

    public static function isSecret(?string $group, ?string $name): bool
    {
        return in_array($name, self::SECRETS[$group] ?? [], true);
    }

    /**
     * เข้ารหัสค่าลับ — ค่าที่เข้ารหัสไว้แล้ว (ถอดได้ด้วย APP_KEY ปัจจุบัน) คืนตามเดิม ไม่เข้ารหัสซ้อน
     */
    public static function encryptSecret(string $value): string
    {
        return self::decryptSecret($value) !== null ? $value : Crypt::encryptString($value);
    }

    /**
     * ถอดรหัสค่าลับ — ไม่ใช่ค่าที่เข้ารหัส / APP_KEY เปลี่ยนไปแล้ว = null
     */
    public static function decryptSecret(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $values  ค่าดิบจาก sys_setting (ค่าลับยังเข้ารหัสอยู่)
     * @return array<string, mixed>
     */
    private static function revealSecrets(string $group, array $values): array
    {
        foreach (self::SECRETS[$group] ?? [] as $name) {
            if (isset($values[$name])) {
                $values[$name] = self::decryptSecret($values[$name]);
            }
        }

        return $values;
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
        Cache::memo()->forget("sys_setting.{$group}");

        // หน้าบ้านใช้ค่าตั้งค่า (ชื่อไซต์/ติดต่อ/social/ภาษา/บทความ ฯลฯ) ใน cache ของตัวเอง — ล้างตามไปด้วย
        FrontCache::forgetAll();
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

    /**
     * รายชื่อ timezone identifier ทั้งหมดที่ PHP รู้จัก สำหรับตัวเลือกในฟอร์มตั้งค่าระบบ (ฟิลด์ `site.timezone`) —
     * ใช้กับ SearchableSelect (พิมพ์ค้นหาได้) แทน dropdown รายการยาว ๆ
     *
     * @return list<array{value: string, label: string}>
     */
    public static function timezoneOptions(): array
    {
        return array_map(
            fn (string $tz) => ['value' => $tz, 'label' => $tz],
            \DateTimeZone::listIdentifiers(),
        );
    }

    /**
     * Measurement ID ของ Google Analytics (sys_setting: google_analytics.tracking_id) — null ถ้ายังไม่ได้ตั้งค่า
     * ใช้ฝัง gtag.js ในหน้าบ้าน (resources/views/app.blade.php) เฉพาะตอนมีค่าเท่านั้น
     */
    public static function googleAnalyticsTrackingId(): ?string
    {
        return self::get('google_analytics', 'tracking_id');
    }
}
