<?php

namespace App\Support\Front;

use App\Models\FileInfo;

/**
 * จุดเดียวที่สร้าง URL ของไฟล์/รูปที่หน้าบ้านใช้ — ตอนนี้ชี้ไป route สาธารณะ `front.file.*` (/file/get/{hash_name} ฯลฯ)
 * ด้วย hash_name (ULID เดาไม่ได้)
 *
 * อนาคต (ไฟล์เฉพาะสมาชิก/บทความลับ): เปลี่ยนที่คลาสนี้ที่เดียวเป็น signed URL (URL::temporarySignedRoute) หรือ route
 * ที่ตรวจสิทธิ์ต่อบทความ — ทุกจุดของหน้าบ้านได้ URL จากที่นี่ (ฝั่ง Vue ไม่ประกอบ URL ไฟล์เอง) จึงไม่ต้องแก้หน้าจอ
 */
final class FrontFile
{
    /**
     * URL แสดงไฟล์ (inline)
     */
    public static function url(?string $hashName): ?string
    {
        return $hashName ? route('front.file.get', ['hashname' => $hashName]) : null;
    }

    /**
     * URL ดาวน์โหลดไฟล์ (Content-Disposition: attachment)
     */
    public static function downloadUrl(?string $hashName): ?string
    {
        return $hashName ? route('front.file.download', ['hashname' => $hashName]) : null;
    }

    /**
     * URL รูปย่อตามความกว้าง — ปัดขึ้นไปขนาดที่อนุญาตที่ใกล้ที่สุด (config front.thumbnail_sizes) ไฟล์ที่ไม่ใช่รูปจะได้ไฟล์เดิม
     */
    public static function thumbnail(?string $hashName, int $width): ?string
    {
        if (! $hashName) {
            return null;
        }

        return route('front.file.thumbnail', ['size' => self::allowedSize($width), 'hashname' => $hashName]);
    }

    /**
     * ขนาด thumbnail ที่อนุญาตที่ใกล้เคียง (ไม่เล็กกว่าที่ขอ) — เกินขนาดใหญ่สุดใช้ขนาดใหญ่สุด
     */
    public static function allowedSize(int $width): int
    {
        $sizes = config('front.thumbnail_sizes', [640]);
        sort($sizes);

        foreach ($sizes as $size) {
            if ($size >= $width) {
                return (int) $size;
            }
        }

        return (int) end($sizes);
    }

    /**
     * ข้อมูลไฟล์ 1 รายการสำหรับหน้าบ้าน (ไม่ส่ง id/path/ผู้อัปโหลด)
     *
     * @return array{name: string, extension: string|null, file_size: int, is_image: bool, url: string|null, download_url: string|null, thumb_url: string|null}|null
     */
    public static function fromFileInfo(?FileInfo $file, int $thumbWidth = 960): ?array
    {
        if (! $file || $file->status !== 'Y') {
            return null;
        }

        return [
            'name' => $file->name,
            'extension' => $file->extension,
            'file_size' => (int) $file->file_size,
            'is_image' => $file->isImage(),
            'url' => self::url($file->hash_name),
            'download_url' => self::downloadUrl($file->hash_name),
            'thumb_url' => $file->isImage() ? self::thumbnail($file->hash_name, $thumbWidth) : null,
        ];
    }
}
