<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ตรวจสอบ Google reCAPTCHA v3 (invisible) — คีย์อ่านจากการตั้งค่ากลุ่ม recaptcha (sys_setting)
 */
class Recaptcha
{
    /** คะแนนขั้นต่ำที่ถือว่าผ่าน (Google แนะนำ 0.5) — ยังไม่มีช่องตั้งค่าใน UI จึงคงที่ในโค้ด */
    private const SCORE_THRESHOLD = 0.5;

    /**
     * ระบบเปิดใช้ reCAPTCHA หรือไม่ — ต้องตั้งค่า "เปิดใช้งาน" และกรอกทั้ง site key + secret ครบ
     */
    public static function enabled(): bool
    {
        return Setting::get('login_back', 'recaptcha_enabled', 'N') === 'Y'
            && Setting::get('recaptcha', 'site_key') !== null
            && Setting::get('recaptcha', 'key_secret') !== null;
    }

    public static function siteKey(): ?string
    {
        return Setting::get('recaptcha', 'site_key');
    }

    /**
     * ยืนยัน token กับ Google — คืน true เฉพาะเมื่อ success และคะแนน >= threshold
     */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        $secret = Setting::get('recaptcha', 'key_secret');

        if (! $token || ! $secret) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            $result = $response->json();

            return (bool) ($result['success'] ?? false)
                && (float) ($result['score'] ?? 0) >= self::SCORE_THRESHOLD;
        } catch (\Throwable $e) {
            // เรียก Google ไม่สำเร็จ (network/timeout) — ถือว่าไม่ผ่าน (fail-closed) เพื่อความปลอดภัย
            Log::warning('reCAPTCHA verify failed: '.$e->getMessage());

            return false;
        }
    }
}
