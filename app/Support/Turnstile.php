<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ตรวจสอบ Cloudflare Turnstile — คีย์อ่านจากการตั้งค่ากลุ่ม turnstile (sys_setting)
 *
 * เลือกใช้ Turnstile แทน Google reCAPTCHA เพราะฟรี/ไม่จำกัดโควตาในทางปฏิบัติ (reCAPTCHA free tier
 * จำกัด 10,000 request/เดือน) — โหมดการแสดงผล (Managed/Non-Interactive/Invisible) กำหนดตอนสร้าง
 * site key ที่ Cloudflare dashboard ไม่ใช่ค่าที่ควบคุมจากโค้ดฝั่งนี้
 */
class Turnstile
{
    /**
     * ระบบเปิดใช้ Turnstile หรือไม่ — ต้องตั้งค่า "เปิดใช้งาน" และกรอกทั้ง site key + secret ครบ
     */
    public static function enabled(): bool
    {
        return Setting::get('login_back', 'captcha_enabled', 'N') === 'Y'
            && Setting::get('turnstile', 'site_key') !== null
            && Setting::get('turnstile', 'key_secret') !== null;
    }

    /**
     * กรอก site key + secret ครบหรือไม่ (ไม่สนตัวเลือกเปิดใช้ของหน้า login) — ใช้กับแบบฟอร์มหน้าบ้าน (ติดต่อเรา)
     * ที่บังคับใช้ CAPTCHA เสมอเมื่อ key ครบ และไม่เปิดรับข้อมูลเลยถ้า key ไม่ครบ
     */
    public static function configured(): bool
    {
        return Setting::get('turnstile', 'site_key') !== null
            && Setting::get('turnstile', 'key_secret') !== null;
    }

    public static function siteKey(): ?string
    {
        return Setting::get('turnstile', 'site_key');
    }

    /**
     * ยืนยัน token กับ Cloudflare — Turnstile ตอบกลับแบบ success/fail ล้วน ไม่มี score เหมือน reCAPTCHA v3
     */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        $secret = Setting::get('turnstile', 'key_secret');

        if (! $token || ! $secret) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) ($response->json('success') ?? false);
        } catch (\Throwable $e) {
            // เรียก Cloudflare ไม่สำเร็จ (network/timeout) — ถือว่าไม่ผ่าน (fail-closed) เพื่อความปลอดภัย
            Log::warning('Turnstile verify failed: '.$e->getMessage());

            return false;
        }
    }
}
