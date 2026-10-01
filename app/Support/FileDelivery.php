<?php

namespace App\Support;

use App\Models\FileInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

/**
 * ฟังก์ชันกลางสำหรับเสิร์ฟไฟล์จาก file_info — ใช้ร่วมกันระหว่าง path หลังบ้านที่ต้อง login
 * (App\Http\Controllers\Admin\System\FileServeController) กับ path สาธารณะในอนาคต (เช่น
 * โลโก้/favicon ที่ไม่ต้อง login) โดย controller แต่ละฝั่งเป็นคนตรวจสอบสิทธิ์เข้าถึงเอง
 * แล้วค่อยเรียกคลาสนี้แค่ "ส่งไฟล์" เพียงอย่างเดียว
 *
 * ใช้ Symfony\BinaryFileResponse เพราะ Laravel เรียก $response->prepare($request) ให้อัตโนมัติ
 * ใน Router::toResponse() ซึ่งจัดการ Range/206 Partial Content (จำเป็นสำหรับเล่น mp3/mp4 บน iOS)
 * ให้เองโดยไม่ต้อง parse header เอง — โค้ดนี้แค่ตั้ง ETag/Last-Modified/Cache-Control ให้ครบ
 *
 * หมายเหตุ: อิงว่า disk ที่ใช้เป็น local filesystem (ดู config('filemanagement.disk')) จึงเรียก
 * ->path() หา absolute path ตรง ๆ ได้ — ถ้าเปลี่ยนไปใช้ disk แบบ remote (S3 ฯลฯ) ในอนาคต ต้องเปลี่ยน
 * มาใช้ Storage::disk()->response()/->temporaryUrl() แทน
 */
class FileDelivery
{
    public const TYPE_SHOW = 'show';

    public const TYPE_DOWNLOAD = 'download';

    public const TYPE_THUMBNAIL = 'thumbnail';

    /**
     * สร้าง response สำหรับส่งไฟล์ 1 รายการ ตามประเภทที่ขอ
     *
     * $public: false (ค่าเริ่มต้น) = ต้อง login ถึงเข้าถึงได้ ห้าม shared cache (proxy/CDN) เก็บ —
     * ใช้ true เฉพาะ path สาธารณะไม่ต้อง login จริง ๆ (เช่น โลโก้/favicon ผ่าน AppAssetController)
     */
    public static function respond(Request $request, FileInfo $file, string $type = self::TYPE_SHOW, ?int $thumbnailWidth = null, bool $public = false): SymfonyResponse
    {
        $disk = config('filemanagement.disk');

        // thumbnail เฉพาะไฟล์รูปภาพ — ไฟล์ประเภทอื่นตกมาที่ show/download ปกติ
        if ($type === self::TYPE_THUMBNAIL && $file->isImage()) {
            return self::respondThumbnail($request, $file, $thumbnailWidth ?? (int) config('filemanagement.thumbnail_default_width'), $public);
        }

        if (! Storage::disk($disk)->exists($file->path)) {
            abort(404);
        }

        $etag = self::etag($file);

        if (self::notModified($request, $etag)) {
            return self::notModifiedResponse($etag);
        }

        $response = new BinaryFileResponse(Storage::disk($disk)->path($file->path));
        $response->headers->set('Content-Type', $file->mime_type ?: 'application/octet-stream');
        self::applyCacheHeaders($response, $etag, $file, $public);

        if ($type === self::TYPE_DOWNLOAD) {
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->name);
        }

        return $response;
    }

    /**
     * เสิร์ฟ thumbnail — generate ครั้งแรกแล้ว cache ไฟล์ที่ resize แล้วไว้บน disk เพื่อไม่ต้อง
     * resize ซ้ำทุก request (ประเด็น performance)
     */
    private static function respondThumbnail(Request $request, FileInfo $file, int $width, bool $public = false): SymfonyResponse
    {
        $disk = config('filemanagement.disk');
        $thumbPath = self::thumbnailPath($file, $width);

        // ปกติสร้างไว้แล้วตั้งแต่ตอนอัปโหลด (pregenerateThumbnails) — ขนาดอื่น/ไฟล์เก่าสร้างตอนถูกขอครั้งแรก
        if (! Storage::disk($disk)->exists($thumbPath)) {
            if (! Storage::disk($disk)->exists($file->path)) {
                abort(404);
            }

            self::generateThumbnails($file, [$width]);
        }

        $etag = self::etag($file, $width);

        if (self::notModified($request, $etag)) {
            return self::notModifiedResponse($etag);
        }

        $response = new BinaryFileResponse(Storage::disk($disk)->path($thumbPath));
        $response->headers->set('Content-Type', $file->mime_type ?: 'application/octet-stream');
        self::applyCacheHeaders($response, $etag, $file, $public);

        return $response;
    }

    /**
     * path ของ thumbnail บน disk — {base_path}/thumbnails/{width}/{hash_name} (หลังบ้าน/หน้าบ้านใช้ไฟล์ชุดเดียวกันตามความกว้าง)
     */
    public static function thumbnailPath(FileInfo $file, int $width): string
    {
        return config('filemanagement.base_path').'/thumbnails/'.$width.'/'.$file->hash_name;
    }

    /**
     * สร้าง thumbnail ทุกขนาดที่ใช้บ่อยไว้ล่วงหน้า (config filemanagement.pregenerate_thumbnail_sizes) — เรียกหลังอัปโหลดรูป
     * (FileController::upload ผ่าน defer() = หลังส่ง response แล้ว ผู้อัปโหลดไม่ต้องรอ) และจากคำสั่ง `php artisan files:thumbnails`
     * ผู้ชมคนแรกจะได้รูปที่ย่อไว้แล้วทันที ไม่ต้องรอ GD ย่อรูปต้นฉบับ (ช้าเมื่อรูปใหญ่)
     * ไม่ใช่รูป / ไม่พบไฟล์ต้นฉบับ / ย่อไม่สำเร็จ (เช่น memory ไม่พอ) = ข้ามเงียบ ๆ — ยังสร้างตอนถูกขอครั้งแรกได้ตามเดิม
     *
     * @param  list<int>|null  $widths
     * @return int จำนวน thumbnail ที่สร้างใหม่
     */
    public static function pregenerateThumbnails(FileInfo $file, ?array $widths = null): int
    {
        if (! $file->isImage() || ! Storage::disk(config('filemanagement.disk'))->exists($file->path)) {
            return 0;
        }

        try {
            return self::generateThumbnails($file, $widths ?? config('filemanagement.pregenerate_thumbnail_sizes', []));
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * สร้าง thumbnail ตามความกว้างที่ระบุ (เฉพาะขนาดที่ยังไม่มี) — อ่าน/decode รูปต้นฉบับครั้งเดียวแล้วย่อทุกขนาดจากต้นฉบับ
     * lock ต่อไฟล์ — request/งานที่สร้าง thumbnail ของไฟล์เดียวกันพร้อมกันไม่ต้อง decode ซ้ำ (กิน memory ของ GD)
     *
     * @param  list<int>  $widths
     */
    private static function generateThumbnails(FileInfo $file, array $widths): int
    {
        $disk = Storage::disk(config('filemanagement.disk'));

        return Cache::lock('thumbnail:'.$file->hash_name, 60)->block(30, function () use ($disk, $file, $widths) {
            // อีก request/งานอาจสร้างเสร็จระหว่างรอ lock — เหลือเฉพาะขนาดที่ยังไม่มี
            $missing = array_values(array_filter(
                array_unique(array_map('intval', $widths)),
                fn (int $width) => $width > 0 && ! $disk->exists(self::thumbnailPath($file, $width)),
            ));

            if ($missing === []) {
                return 0;
            }

            $original = (new ImageManager(new Driver))->read($disk->path($file->path));

            foreach ($missing as $width) {
                $image = clone $original;
                $image->scaleDown(width: $width); // ไม่ขยายรูปที่เล็กกว่าขนาดที่ขอ

                $disk->put(self::thumbnailPath($file, $width), (string) $image->encodeByExtension($file->extension, quality: 82));
            }

            return count($missing);
        });
    }

    private static function etag(FileInfo $file, ?int $width = null): string
    {
        return md5($file->hash_name.'|'.$file->file_size.'|'.$file->updated_at.'|'.$width);
    }

    private static function notModified(Request $request, string $etag): bool
    {
        $header = trim((string) $request->headers->get('If-None-Match'), " \t\"");

        return $header !== '' && $header === $etag;
    }

    private static function notModifiedResponse(string $etag): SymfonyResponse
    {
        return response('', SymfonyResponse::HTTP_NOT_MODIFIED)->header('ETag', '"'.$etag.'"');
    }

    private static function applyCacheHeaders(BinaryFileResponse $response, string $etag, FileInfo $file, bool $public = false): void
    {
        $response->setEtag($etag);
        $response->setLastModified($file->updated_at);

        if ($public) {
            $response->setPublic(); // asset สาธารณะ (โลโก้/favicon) — ให้ shared cache (proxy/CDN) เก็บได้
        } else {
            $response->setPrivate(); // ต้อง login ถึงเข้าถึงได้ — ห้าม shared cache (proxy/CDN) เก็บ
        }

        $response->setMaxAge(86400);
    }
}
