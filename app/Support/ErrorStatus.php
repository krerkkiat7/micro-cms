<?php

namespace App\Support;

/**
 * สถานะ HTTP ของหน้า error ที่ระบบออกแบบข้อความไว้ — ใช้ร่วมกันทั้งหน้าบ้าน (lang/{ภาษา}/front.php error_*),
 * หลังบ้าน (lang/th/error.php) และหน้าสำรอง Blade (resources/views/errors/*)
 */
final class ErrorStatus
{
    /** สถานะที่มีข้อความเฉพาะ — สถานะอื่นใช้ข้อความกลาง 4xx / 5xx */
    public const KNOWN = [400, 403, 404, 405, 410, 413, 419, 429, 500, 503];

    public static function isError(int $status): bool
    {
        return $status >= 400 && $status < 600;
    }

    /** key ของข้อความ (สถานะเอง หรือ '4xx' / '5xx') */
    public static function textKey(int $status): string
    {
        if (in_array($status, self::KNOWN, true)) {
            return (string) $status;
        }

        return $status >= 500 ? '5xx' : '4xx';
    }

    /**
     * แสดงรหัสอ้างอิงหรือไม่ — error ฝั่งเซิร์ฟเวอร์ (5xx) ยกเว้น 503 (ปิดปรับปรุงตั้งใจ ไม่ใช่ความผิดพลาด)
     */
    public static function hasReference(int $status): bool
    {
        return $status >= 500 && $status !== 503;
    }

    /**
     * จำลอง error สำหรับดูตัวอย่างหน้า (route test-error เฉพาะ APP_ENV=local) — 500 โยน exception จริง
     * (ให้ถูก report ลง log พร้อมรหัสอ้างอิง) สถานะอื่นใช้ abort()
     */
    public static function simulate(int $status): never
    {
        if ($status === 500) {
            throw new \RuntimeException('ทดสอบ error 500 (route test-error) — ข้อความนี้ต้องไม่แสดงบนหน้าเว็บ');
        }

        abort($status);
    }
}
