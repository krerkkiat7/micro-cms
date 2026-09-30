<?php

namespace App\Support\Admin;

use App\Support\AppAsset;
use App\Support\ErrorReference;
use App\Support\ErrorStatus;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * หน้า error ของหลังบ้าน (Pages/Admin/Error.vue) — request /admin* ที่ไม่ใช่ JSON, ทุกสถานะ 4xx/5xx, ข้อความภาษาไทย (lang/th/error.php)
 * คู่กับ App\Support\Front\FrontErrorPage ของหน้าบ้าน
 *
 * render แบบ Inertia (ไม่ใช่ HTML ดิบ) — การเปลี่ยนหน้าแบบ Inertia ที่ได้ error จะแสดงเป็นหน้าเต็มแทน modal HTML ของ Laravel
 * ส่ง siteName/appLogoUrl เป็น prop ตรง เพราะ 404 ของ URL ที่ไม่มี route ไม่ผ่าน HandleInertiaRequests (ไม่มี shared props)
 * 5xx: บอกแค่ว่าเกิดข้อผิดพลาด + รหัสอ้างอิง (ไม่ใช้ message ของ exception — รวม abort(403, '...') ด้วย); เปิด APP_DEBUG ยังใช้หน้า debug ของ Laravel
 * สร้างหน้าไม่ได้ (เช่น ฐานข้อมูลล่ม) → คืน response เดิม = หน้าสำรอง resources/views/errors/{4xx,5xx}.blade.php
 */
final class AdminErrorPage
{
    public static function isAdminRequest(Request $request): bool
    {
        return $request->is('admin', 'admin/*');
    }

    public static function render(Response $response, Request $request): Response
    {
        $status = $response->getStatusCode();

        if (! ErrorStatus::isError($status)
            || ! self::isAdminRequest($request)
            || $request->expectsJson()
            || ($status >= 500 && config('app.debug'))) {
            return $response;
        }

        try {
            App::setLocale('th');
            $key = ErrorStatus::textKey($status);

            return Inertia::render('Admin/Error', [
                'status' => $status,
                'title' => (string) trans("error.title.{$key}"),
                'description' => (string) trans("error.description.{$key}"),
                'reference' => ErrorStatus::hasReference($status) ? ErrorReference::current() : null,
                'homeUrl' => route('admin.home'),
                'loginUrl' => route('admin.login'),
                'siteName' => Setting::siteName(),
                'appLogoUrl' => AppAsset::logo() ? route('app.logo') : null,
            ])->toResponse($request)->setStatusCode($status);
        } catch (Throwable) {
            return $response;
        }
    }
}
