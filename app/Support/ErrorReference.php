<?php

namespace App\Support;

use App\Support\Front\FrontAuth;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * รหัสอ้างอิงของ error (เช่น ERR-7F3K2Q9A) + ข้อมูลประกอบสำหรับ log — ใช้กับ error 5xx
 *
 * รหัสสร้างครั้งเดียวต่อ request (เก็บใน request()->attributes) จึงเป็นค่าเดียวกันทั้งใน log (context ของ exception ใน bootstrap/app.php)
 * และที่แสดงบนหน้า error — ผู้ใช้แจ้งรหัสนี้มา ผู้ดูแลค้นใน storage/logs/error-*.log ได้ทันที (หน้า error ไม่แสดงสาเหตุจริง)
 */
final class ErrorReference
{
    /** ตัวอักษรที่ไม่กำกวมเวลาอ่าน/พิมพ์ตาม (ไม่มี 0/O, 1/I/L) */
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    private const ATTRIBUTE = 'error_reference';

    public static function current(): string
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::ATTRIBUTE)) {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $attributes->set(self::ATTRIBUTE, "ERR-{$code}");
        }

        return $attributes->get(self::ATTRIBUTE);
    }

    /**
     * channel ของ log error ตามฝั่งที่เกิด (config/logging.php):
     * error_admin = /admin* และคำสั่ง artisan/queue ที่ไม่ได้มาจากหน้าเว็บ (ไม่มี route), error_front = ที่เหลือทั้งหมด (หน้าบ้าน + path สาธารณะ)
     */
    public static function channel(): string
    {
        $request = request();

        if ($request->is('admin', 'admin/*') || (app()->runningInConsole() && $request->route() === null)) {
            return 'error_admin';
        }

        return 'error_front';
    }

    /**
     * ข้อมูลประกอบของ request ที่เกิด error — เก็บเฉพาะ "ชื่อ" ฟิลด์ที่ส่งมา ไม่เก็บค่า (กันรหัสผ่าน/ข้อมูลส่วนบุคคลหลุดลง log)
     * ทุกค่าอ่านแบบปลอดภัย: error อาจเกิดก่อน session/auth พร้อม หรือระหว่างฐานข้อมูลล่ม
     *
     * @return array<string, mixed>
     */
    public static function context(): array
    {
        $request = request();

        return array_filter([
            'reference' => self::current(),
            'url' => self::safe(fn () => $request->fullUrl()),
            'method' => self::safe(fn () => $request->method()),
            'route' => self::safe(fn () => $request->route()?->getName()),
            'user_id' => self::safe(fn () => $request->hasSession() ? Auth::guard('web')->id() : null),
            'front_user_id' => self::safe(fn () => $request->hasSession() ? FrontAuth::id() : null),
            'ip' => self::safe(fn () => ClientIp::from($request)),
            'user_agent' => self::safe(fn () => mb_substr((string) $request->userAgent(), 0, 500)),
            'referer' => self::safe(fn () => $request->headers->get('referer')),
            'input_keys' => self::safe(fn () => array_keys($request->except(['_token']))),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private static function safe(callable $read): mixed
    {
        try {
            return $read();
        } catch (Throwable) {
            return null;
        }
    }
}
