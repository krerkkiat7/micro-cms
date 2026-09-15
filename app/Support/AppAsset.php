<?php

namespace App\Support;

use App\Models\FileInfo;
use Illuminate\Support\Facades\Cache;

/**
 * โลโก้/favicon ของระบบ — ค่าที่ตั้งไว้เก็บเป็น `file_info.id` ใน sys_setting (group=site,
 * name=logo_id/favicon_id) อ่านผ่าน App\Support\Setting (cache 1 วันอยู่แล้ว) แต่ยัง resolve เป็น
 * FileInfo อีกทีทุกครั้ง (query ตาม id) — เพราะ route เสิร์ฟไฟล์ (/apps/logo.png, /apps/favicon.ico)
 * ถูกเรียกทุกครั้งที่โหลดหน้าเว็บ (ทุกคนทุกหน้า ไม่ใช่แค่หลังบ้าน) จึง cache ผล FileInfo แยกไว้เองอีกชั้น
 * กัน query ซ้ำถี่ ๆ — ต้องเคลียร์คู่กับแคชกลุ่ม 'site' เสมอ (ดู forgetCache() และจุดเรียกใน SettingController)
 */
class AppAsset
{
    private const TTL_DAYS = 1;

    public static function logo(): ?FileInfo
    {
        return self::resolve('logo_id', 'app_asset.logo');
    }

    public static function favicon(): ?FileInfo
    {
        return self::resolve('favicon_id', 'app_asset.favicon');
    }

    /**
     * ล้างแคชโลโก้/favicon — เรียกทุกจุดที่ล้าง/บันทึกแคชกลุ่ม 'site' (SettingController)
     */
    public static function forgetCache(): void
    {
        Cache::forget('app_asset.logo');
        Cache::forget('app_asset.favicon');
    }

    private static function resolve(string $settingName, string $cacheKey): ?FileInfo
    {
        $id = Setting::get('site', $settingName);

        if (! $id) {
            return null;
        }

        return Cache::remember($cacheKey, now()->addDays(self::TTL_DAYS), fn () => FileInfo::query()->find($id));
    }
}
