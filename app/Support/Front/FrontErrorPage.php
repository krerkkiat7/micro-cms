<?php

namespace App\Support\Front;

use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * หน้า error ของหน้าบ้าน (Front/Error.vue ใน layout หน้าบ้าน) — แทนหน้า error มาตรฐานของ Laravel เฉพาะ request หน้าบ้าน
 * (ไม่ใช่ /admin, ไม่ใช่ไฟล์ /file/*, /apps/*, ไม่ใช่ JSON) ภาษาอ่านจากส่วนแรกของ URL (ไม่ตรง = ภาษาหลัก)
 * 500 ตอนเปิด APP_DEBUG ยังใช้หน้า debug ของ Laravel ตามเดิม
 */
final class FrontErrorPage
{
    private const STATUSES = [403, 404, 419, 429, 500, 503];

    public static function render(Response $response, Request $request): Response
    {
        $status = $response->getStatusCode();

        if (! in_array($status, self::STATUSES, true)
            || $request->expectsJson()
            || $request->is('admin', 'admin/*', 'file/*', 'apps/*', 'front/*', 'up')
            || ($status >= 500 && config('app.debug'))) {
            return $response;
        }

        try {
            $segment = $request->segment(1);
            $lang = in_array($segment, Setting::selectedLanguages(), true) ? $segment : Setting::defaultLanguage();
            App::setLocale($lang);
            $layout = FrontLayoutData::forLanguage($lang);

            return Inertia::render('Front/Error', [
                'status' => $status,
                'front' => $layout,
                'seo' => SeoMeta::make($lang, [
                    'title' => (string) trans("front.error_title.{$status}", [], $lang),
                    'canonical' => $request->url(),
                    'noindex' => true,
                ]),
                'accessLog' => ['token' => null],
                'flash' => ['success' => null, 'successId' => null],
            ])->toResponse($request)->setStatusCode($status);
        } catch (Throwable) {
            // สร้างหน้า error ของหน้าบ้านไม่ได้ (เช่น ฐานข้อมูลล่ม) — ใช้หน้า error มาตรฐาน
            return $response;
        }
    }
}
