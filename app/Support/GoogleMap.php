<?php

namespace App\Support;

/**
 * สร้าง URL สำหรับ iframe แผนที่ Google Map จากพิกัด — API key อ่านจากตั้งค่าระบบกลุ่ม google_map
 * มี key = Maps Embed API (แสดงผลเรียบร้อย), ไม่มี key = ลิงก์ embed แบบเก่าที่ไม่ใช้ key
 * (ยังแสดงได้แต่หน้าตาไม่เรียบร้อย และ Google อาจเลิกรองรับได้ทุกเมื่อ)
 */
class GoogleMap
{
    public static function apiKey(): ?string
    {
        return Setting::get('google_map', 'api_key');
    }

    public static function embedUrl(float $latitude, float $longitude, string $lang = 'th', int $zoom = 16): string
    {
        $query = self::coordinates($latitude, $longitude);
        $key = self::apiKey();

        if ($key) {
            return 'https://www.google.com/maps/embed/v1/place?'.http_build_query([
                'key' => $key,
                'q' => $query,
                'zoom' => $zoom,
                'language' => $lang,
            ]);
        }

        return 'https://maps.google.com/maps?'.http_build_query([
            'q' => $query,
            'z' => $zoom,
            'hl' => $lang,
            'output' => 'embed',
        ]);
    }

    /**
     * ลิงก์เปิดตำแหน่งในเว็บ/แอป Google Maps (ปุ่ม "เปิดใน Google Maps")
     */
    public static function linkUrl(float $latitude, float $longitude): string
    {
        return 'https://www.google.com/maps/search/?'.http_build_query([
            'api' => 1,
            'query' => self::coordinates($latitude, $longitude),
        ]);
    }

    private static function coordinates(float $latitude, float $longitude): string
    {
        return rtrim(rtrim(sprintf('%.7F', $latitude), '0'), '.').','.rtrim(rtrim(sprintf('%.7F', $longitude), '0'), '.');
    }
}
