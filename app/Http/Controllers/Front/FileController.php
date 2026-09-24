<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\FileInfo;
use App\Support\FileCache;
use App\Support\FileDelivery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * เสิร์ฟไฟล์/รูปที่เนื้อหาหน้าบ้านใช้ (/file/get/{hash_name} ฯลฯ) — ไม่ต้อง login, อ้างอิงด้วย hash_name (ULID เดาไม่ได้)
 * ส่ง Cache-Control แบบ public ให้ browser/CDN เก็บได้ (FileDelivery::respond(..., public: true))
 *
 * ระยะถัดไป (ไฟล์เฉพาะสมาชิก/บทความลับ): URL ทั้งหมดสร้างจาก App\Support\Front\FrontFile ที่เดียว — เปลี่ยนเป็น signed URL
 * หรือเช็กสิทธิ์ต่อบทความที่นี่ได้โดยไม่ต้องแก้หน้าจอ ดู docs/PRD-front.md §ไฟล์
 */
class FileController extends Controller
{
    public function show(Request $request, string $hashname): SymfonyResponse
    {
        return FileDelivery::respond($request, $this->resolve($hashname), FileDelivery::TYPE_SHOW, null, true);
    }

    public function download(Request $request, string $hashname): SymfonyResponse
    {
        return FileDelivery::respond($request, $this->resolve($hashname), FileDelivery::TYPE_DOWNLOAD, null, true);
    }

    /**
     * ขนาดต้องเป็นค่าที่อนุญาต (config front.thumbnail_sizes) — กันการสั่ง resize ขนาดใด ๆ ก็ได้จนเปลือง CPU/disk
     */
    public function thumbnail(Request $request, string $size, string $hashname): SymfonyResponse
    {
        if (! in_array((int) $size, config('front.thumbnail_sizes', []), true)) {
            abort(404);
        }

        return FileDelivery::respond($request, $this->resolve($hashname), FileDelivery::TYPE_THUMBNAIL, (int) $size, true);
    }

    private function resolve(string $hashname): FileInfo
    {
        $file = FileCache::get($hashname);

        if (! $file) {
            abort(404);
        }

        return $file;
    }
}
