<?php

namespace App\Support;

use App\Models\FileInfo;
use Illuminate\Support\Facades\Cache;

/**
 * cache-first lookup ของ file_info ด้วย hash_name — เรียกซ้ำ ๆ ตอนเสิร์ฟไฟล์ (รูปหน้าแรก, thumbnail
 * ในรายการ ฯลฯ) จึงแคชผลไว้กันอ่าน DB ทุก request (ดู App\Support\FileDelivery)
 *
 * driver cache ของโปรเจกต์เป็น `database` (ดู .env) ซึ่งไม่รองรับ tag เหมือน App\Support\Setting
 * และ key ที่นี่เป็น dynamic ตาม hash_name (enumerate ทั้งหมดไม่ได้) — forgetAll() จึงทำได้แค่
 * flush cache ทั้ง store (กระทบ cache อื่นที่ไม่ผูก tag ด้วย เช่น sys_setting — ยอมรับได้เพราะแค่ทำให้
 * โหลดจาก DB ใหม่รอบเดียว ไม่ทำข้อมูลเสียหาย)
 */
class FileCache
{
    private const TTL_HOURS = 6;

    /**
     * หาไฟล์จาก hash_name แบบ cache-first — คืน null ถ้าไม่พบ/ถูกลบ/status ไม่ใช่ Y
     */
    public static function get(string $hashName): ?FileInfo
    {
        return Cache::remember(
            self::key($hashName),
            now()->addHours(self::TTL_HOURS),
            fn () => FileInfo::query()->where('hash_name', $hashName)->where('status', 'Y')->first(),
        );
    }

    /**
     * ล้าง cache ของไฟล์เดียว — เรียกทันทีหลังลบ/แก้ไขไฟล์นั้น
     */
    public static function forget(string $hashName): void
    {
        Cache::forget(self::key($hashName));
    }

    /**
     * ล้าง cache ไฟล์ทั้งหมด — ใช้จากหน้า "ล้างแคช" ของตั้งค่าระบบ
     */
    public static function forgetAll(): void
    {
        Cache::flush();
    }

    private static function key(string $hashName): string
    {
        return "file_info.{$hashName}";
    }
}
