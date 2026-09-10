<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * หา IP จริงของ client — เมื่ออยู่หลัง proxy/CDN ให้อ่านจาก header ก่อน
 * (CF-Connecting-IP → X-Real-IP → X-Forwarded-For ตัวแรกที่เป็น IP ถูกต้อง)
 * ค่อย fallback เป็น $request->ip()
 *
 * หมายเหตุ: header เหล่านี้ปลอมได้ถ้า client ต่อตรงโดยไม่ผ่าน proxy —
 * สำหรับ log (ไม่ใช่ security control) ยอมรับความเสี่ยงนี้ได้
 */
class ClientIp
{
    public static function from(?Request $request = null): ?string
    {
        $request ??= request();

        foreach (['CF-Connecting-IP', 'X-Real-IP'] as $header) {
            $ip = trim((string) $request->headers->get($header));

            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
        }

        foreach (explode(',', (string) $request->headers->get('X-Forwarded-For')) as $ip) {
            $ip = trim($ip);

            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
        }

        return $request->ip();
    }
}
