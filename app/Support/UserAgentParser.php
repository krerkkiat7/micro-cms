<?php

namespace App\Support;

/**
 * แยกข้อมูลคร่าว ๆ จาก User-Agent string — เบราว์เซอร์ / ระบบปฏิบัติการ / ชนิดอุปกรณ์ / bot
 *
 * เป็น heuristic เบา ๆ ตั้งใจไม่พึ่ง composer package (UA parser ทุกตัวต้องอัปเดต regex เรื่อย ๆ
 * ตามเบราว์เซอร์/อุปกรณ์ใหม่ ๆ) — ครอบเคสที่พบบ่อยพอสำหรับสถิติหลังบ้าน ค่าอื่นปล่อยเป็น null
 */
class UserAgentParser
{
    /**
     * @return array{
     *     browser: string|null,
     *     browser_version: string|null,
     *     platform: string|null,
     *     device_type: string|null,
     *     mobile: string|null,
     *     robot: string|null,
     * }
     */
    public static function parse(?string $ua): array
    {
        $empty = [
            'browser' => null,
            'browser_version' => null,
            'platform' => null,
            'device_type' => null,
            'mobile' => null,
            'robot' => null,
        ];

        $ua = trim((string) $ua);

        if ($ua === '') {
            return $empty;
        }

        // --- bot / crawler ---
        if (preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|mediapartners|embedly|preview|monitoring|headless)/i', $ua, $m)) {
            return array_merge($empty, [
                'robot' => strtolower($m[1]),
                'device_type' => 'robot',
                'platform' => self::platform($ua),
            ]);
        }

        [$browser, $version] = self::browser($ua);

        return [
            'browser' => $browser,
            'browser_version' => $version,
            'platform' => self::platform($ua),
            'device_type' => self::deviceType($ua),
            'mobile' => self::mobile($ua),
            'robot' => null,
        ];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private static function browser(string $ua): array
    {
        // เรียงจากเฉพาะเจาะจงไปทั่วไป (Edge/Opera ปลอมตัวมี "Chrome" ใน UA ด้วย)
        $patterns = [
            'Microsoft Edge' => '/Edg(?:A|iOS|)?\/([0-9.]+)/',
            'Opera' => '/(?:OPR|Opera)\/([0-9.]+)/',
            'Samsung Internet' => '/SamsungBrowser\/([0-9.]+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/([0-9.]+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/([0-9.]+)/',
            'Safari' => '/Version\/([0-9.]+).*Safari/',
            'Internet Explorer' => '/(?:MSIE |rv:)([0-9.]+)/',
        ];

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                return [$name, $m[1] ?? null];
            }
        }

        return [null, null];
    }

    private static function platform(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/Windows NT/i', $ua) => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod|iOS/i', $ua) => 'iOS',
            (bool) preg_match('/Mac OS X/i', $ua) => 'macOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/CrOS/i', $ua) => 'ChromeOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => null,
        };
    }

    private static function deviceType(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Nexus (?:7|9|10)/i', $ua) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }

    private static function mobile(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/iPad/i', $ua) => 'iPad',
            (bool) preg_match('/iPhone/i', $ua) => 'iPhone',
            (bool) preg_match('/iPod/i', $ua) => 'iPod',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/Windows Phone/i', $ua) => 'Windows Phone',
            default => null,
        };
    }
}
