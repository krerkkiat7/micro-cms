<?php

namespace App\Http\Controllers;

use App\Support\AppAsset;
use App\Support\FileDelivery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * เสิร์ฟโลโก้/favicon สาธารณะของระบบ (/apps/logo.png, /apps/favicon.ico) — ไม่ต้อง login และไม่มี
 * prefix ภาษา {lang} เพราะเป็น asset ที่ทั้งฝั่งแอดมินและหน้าบ้านใช้ร่วมกัน (ไม่ผูกกับ Admin/ หรือ
 * Front/ จึงวางไว้ระดับบนสุดของ Controllers) ชื่อ route (app.logo/app.favicon) เป็นข้อยกเว้นของ
 * convention front.* และ admin.* ปกติ — กำหนดไว้ตามที่ต้องการโดยเฉพาะ
 *
 * ยังไม่ได้ตั้งค่าไว้ (App\Support\AppAsset คืน null) → เสิร์ฟไฟล์ default ที่แถมมากับระบบแทน
 */
class AppAssetController extends Controller
{
    public function logo(Request $request): SymfonyResponse
    {
        $file = AppAsset::logo();

        if ($file) {
            return FileDelivery::respond($request, $file, FileDelivery::TYPE_SHOW, public: true);
        }

        return $this->defaultAsset($request, public_path('images/default-logo.png'), 'image/png');
    }

    public function favicon(Request $request): SymfonyResponse
    {
        $file = AppAsset::favicon();

        if ($file) {
            return FileDelivery::respond($request, $file, FileDelivery::TYPE_SHOW, public: true);
        }

        return $this->defaultAsset($request, public_path('favicon.ico'), 'image/x-icon');
    }

    /**
     * เสิร์ฟไฟล์ default ที่แถมมากับระบบใน public/ (ไม่ได้อยู่ใน file_info) — ตั้ง ETag/Cache-Control
     * เองแบบง่าย ๆ ให้พฤติกรรม cache ฝั่งเบราว์เซอร์ใกล้เคียงกับกรณีตั้งค่าไว้จริง
     */
    private function defaultAsset(Request $request, string $path, string $mimeType): SymfonyResponse
    {
        if (! is_file($path)) {
            abort(404);
        }

        $mtime = filemtime($path);
        $etag = md5($path.'|'.$mtime);

        $ifNoneMatch = trim((string) $request->headers->get('If-None-Match'), " \t\"");
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            return response('', SymfonyResponse::HTTP_NOT_MODIFIED)->header('ETag', '"'.$etag.'"');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $mimeType);
        $response->setEtag($etag);
        $response->setLastModified((new \DateTimeImmutable)->setTimestamp($mtime));
        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
}
