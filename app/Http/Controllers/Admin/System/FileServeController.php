<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\FileInfo;
use App\Support\FileCache;
use App\Support\FileDelivery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * เสิร์ฟไฟล์จากโมดูลจัดการไฟล์ผ่าน hash_name — อยู่ใต้ middleware `auth` เท่านั้น (ไม่ผูกกับ
 * เจ้าของไฟล์ เพราะ hash_name เป็น ULID เดาไม่ได้ และผู้ใช้หลังบ้านที่ login แล้วดูได้ทุกคน)
 *
 * logic การส่งไฟล์จริงอยู่ใน App\Support\FileDelivery (ฟังก์ชันกลาง เผื่อ path สาธารณะ เช่น
 * โลโก้/favicon ในอนาคตเรียกใช้ซ้ำได้)
 */
class FileServeController extends Controller
{
    public function show(Request $request, string $hashname): SymfonyResponse
    {
        return FileDelivery::respond($request, $this->resolve($hashname), FileDelivery::TYPE_SHOW);
    }

    public function download(Request $request, string $hashname): SymfonyResponse
    {
        return FileDelivery::respond($request, $this->resolve($hashname), FileDelivery::TYPE_DOWNLOAD);
    }

    /**
     * รับ route parameter ผ่าน $request->route() ตรง ๆ แทนพารามิเตอร์ของเมธอด — เพราะ route นี้ถูกผูก
     * จาก 2 URI ที่มี {size}/{hashname} เรียงคนละลำดับกัน (มี size หรือไม่มีก็ได้) และ Laravel bind
     * พารามิเตอร์ primitive แบบเรียงตามตำแหน่งในปกติของ URI ไม่ใช่ตามชื่อพารามิเตอร์ของเมธอด —
     * ถ้าประกาศเป็น string $hashname, ?string $size ตรง ๆ จะได้ค่าสลับกันเมื่อ route มี {size} นำหน้า
     */
    public function thumbnail(Request $request): SymfonyResponse
    {
        $hashname = (string) $request->route('hashname');
        $size = $request->route('size');

        if ($size !== null && ! in_array((int) $size, config('filemanagement.admin_thumbnail_sizes'), true)) {
            abort(404);
        }

        return FileDelivery::respond(
            $request,
            $this->resolve($hashname),
            FileDelivery::TYPE_THUMBNAIL,
            $size !== null ? (int) $size : null,
        );
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
