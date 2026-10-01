<?php

namespace App\Support\Front;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * cache ข้อมูลหน้าบ้าน (template/เมนู/ตั้งค่าไซต์, หน้าเพจ, หมวดหมู่/บทความ, intropage) — key ทั้งหมดขึ้นต้น `front.v{version}.`
 *
 * ล้างทั้งหมดด้วยการเพิ่ม "version" (forgetAll) แทน Cache::flush() เพราะ cache store ของโปรเจกต์ไม่รองรับ tag และ flush
 * จะล้าง session/cache อื่นไปด้วย — key ของ version เก่าไม่ถูกอ่านอีกและหมดอายุเองตาม TTL
 *
 * ถูกล้างอัตโนมัติเมื่อบันทึก/ลบข้อมูลที่หน้าบ้านใช้ (model ที่ใช้ trait App\Models\Concerns\FlushesFrontCache)
 * และจากปุ่ม "ล้างแคชหน้าบ้าน" / "ล้างแคชทั้งหมด" ในหลังบ้าน (SettingController)
 */
final class FrontCache
{
    private const VERSION_KEY = 'front.version';

    /** version ที่อ่านแล้วใน request นี้ — กันอ่าน cache ซ้ำหลายรอบต่อ request */
    private static ?int $version = null;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, int $seconds, Closure $callback): mixed
    {
        if ($seconds <= 0) {
            return $callback();
        }

        return Cache::remember(self::key($key), $seconds, $callback);
    }

    /**
     * ล้าง cache หน้าบ้านทั้งหมด (เปลี่ยน version)
     */
    public static function forgetAll(): void
    {
        $next = self::version() + 1;
        Cache::forever(self::VERSION_KEY, $next);
        self::$version = $next;
    }

    /**
     * ล้าง cache หน้าบ้านเพราะข้อมูลเปลี่ยน (เรียกจาก trait FlushesFrontCache ทุกแถวที่บันทึก/ลบ) — บันทึกฟอร์มเดียวอาจแตะหลายสิบแถว
     * (บทความ + part + ไฟล์ + detail) จึงล้างทันทีแค่ครั้งแรกของ request แล้วล้างซ้ำอีกครั้งเดียวหลังจบ request (defer)
     * เผื่อหน้าบ้านสร้าง cache ใหม่ระหว่างที่แถวที่เหลือยังบันทึกไม่เสร็จ
     * คำสั่ง console / เทส (ไม่มี HTTP request จริง) ล้างทันทีทุกครั้งเหมือนเดิม
     */
    public static function flushForChange(): void
    {
        if (app()->runningInConsole()) {
            self::forgetAll();

            return;
        }

        $attributes = request()->attributes;

        if (! $attributes->get('front_cache_flushed')) {
            $attributes->set('front_cache_flushed', true);
            self::forgetAll();

            return;
        }

        defer(fn () => self::forgetAll(), 'front-cache-final-flush');
    }

    public static function version(): int
    {
        return self::$version ??= (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    /**
     * ลืม version ที่จำไว้ใน process (ใช้ในเทส / worker ที่รันยาว)
     */
    public static function resetState(): void
    {
        self::$version = null;
    }

    private static function key(string $key): string
    {
        return 'front.v'.self::version().'.'.$key;
    }
}
