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
        $thumbPath = config('filemanagement.base_path').'/thumbnails/'.$width.'/'.$file->hash_name;

        if (! Storage::disk($disk)->exists($thumbPath)) {
            if (! Storage::disk($disk)->exists($file->path)) {
                abort(404);
            }

            // lock ต่อไฟล์+ขนาด — request แรกพร้อมกันหลายตัวไม่ต้อง decode/resize รูปต้นฉบับซ้ำ (กิน memory ของ GD)
            Cache::lock('thumbnail:'.$width.':'.$file->hash_name, 30)->block(20, function () use ($disk, $thumbPath, $file, $width) {
                if (Storage::disk($disk)->exists($thumbPath)) {
                    return; // อีก request สร้างเสร็จระหว่างรอ lock
                }

                $manager = new ImageManager(new Driver);
                $image = $manager->read(Storage::disk($disk)->path($file->path));
                $image->scaleDown(width: $width); // ไม่ขยายรูปที่เล็กกว่าขนาดที่ขอ

                Storage::disk($disk)->put($thumbPath, (string) $image->encodeByExtension($file->extension, quality: 82));
            });
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
