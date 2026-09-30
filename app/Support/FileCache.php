<?php

namespace App\Support;

use App\Models\FileInfo;
use Illuminate\Support\Facades\Cache;

/**
 * cache-first lookup ของ file_info ด้วย hash_name — เรียกซ้ำ ๆ ตอนเสิร์ฟไฟล์ (รูปหน้าแรก, thumbnail
 * ในรายการ ฯลฯ) จึงแคชผลไว้กันอ่าน DB ทุก request (ดู App\Support\FileDelivery)
 *
 * key มี "version" (แบบเดียวกับ App\Support\Front\FrontCache) — forgetAll() แค่เพิ่ม version แทน Cache::flush()
 * ที่จะล้าง cache ทั้ง store ไปด้วย (ตัวนับ rate limit ของ login/ติดต่อเรา, ตั้งค่าระบบ, lock ฯลฯ)
 * ผลที่ "ไม่พบไฟล์" ก็ cache ไว้ช่วงสั้น ๆ ด้วย กัน request hash ที่ไม่มีจริงซ้ำ ๆ ยิงเข้า DB ทุกครั้ง
 */
class FileCache
{
    private const TTL_HOURS = 6;

    /** อายุ cache ของผล "ไม่พบไฟล์" (นาที) */
    private const MISSING_TTL_MINUTES = 10;

    private const VERSION_KEY = 'file_info.version';

    /**
     * หาไฟล์จาก hash_name แบบ cache-first — คืน null ถ้าไม่พบ/ถูกลบ/status ไม่ใช่ Y
     */
    public static function get(string $hashName): ?FileInfo
    {
        $key = self::key($hashName);
        $cached = Cache::get($key);

        if ($cached instanceof FileInfo) {
            return $cached;
        }

        if ($cached === false) {
            return null; // เคยค้นแล้วไม่พบ
        }

        $file = FileInfo::query()->where('hash_name', $hashName)->where('status', 'Y')->first();

        Cache::put($key, $file ?? false, $file
            ? now()->addHours(self::TTL_HOURS)
            : now()->addMinutes(self::MISSING_TTL_MINUTES));

        return $file;
    }

    /**
     * ล้าง cache ของไฟล์เดียว — เรียกทันทีหลังลบ/แก้ไขไฟล์นั้น
     */
    public static function forget(string $hashName): void
    {
        Cache::forget(self::key($hashName));
    }

    /**
     * ล้าง cache ไฟล์ทั้งหมด (เพิ่ม version — key ของ version เก่าไม่ถูกอ่านอีกและหมดอายุเองตาม TTL)
     * ใช้จากหน้า "ล้างแคช" ของตั้งค่าระบบ
     */
    public static function forgetAll(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    private static function key(string $hashName): string
    {
        return 'file_info.v'.self::version().".{$hashName}";
    }
}
