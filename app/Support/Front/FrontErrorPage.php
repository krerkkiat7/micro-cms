<?php

namespace App\Support\Front;

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
 * หน้า error ของหน้าบ้าน (Pages/Front/Error.vue) — แทนหน้า error มาตรฐานของ Laravel เฉพาะ request หน้าบ้าน
 * (ไม่ใช่ /admin, ไม่ใช่ไฟล์ /file/*, /apps/*, endpoint /front/*, ไม่ใช่ JSON) — รับทุกสถานะ 4xx/5xx (ข้อความดู ErrorStatus)
 *
 * - ข้อความแยกตามภาษาจากส่วนแรกของ URL (ไม่ตรงภาษาที่เปิดใช้ = ภาษาหลัก) — lang/{ภาษา}/front.php
 * - layout เปล่า: ส่งเฉพาะข้อมูลระบบที่ใช้ (ชื่อเว็บ/โลโก้/ภาษา/ข้อความ) ไม่โหลด template/เมนู — ตอนระบบมีปัญหาไม่ควร query หนักเพิ่ม
 * - 5xx: บอกแค่ว่าเกิดข้อผิดพลาด + รหัสอ้างอิง (ErrorReference) ไม่แสดงสาเหตุจริง; เปิด APP_DEBUG ยังใช้หน้า debug ของ Laravel
 * - สร้างหน้าไม่ได้ (เช่น ฐานข้อมูลล่ม) → คืน response เดิม ซึ่งเป็นหน้าสำรอง resources/views/errors/{4xx,5xx}.blade.php
 */
final class FrontErrorPage
{
    public static function render(Response $response, Request $request): Response
    {
        $status = $response->getStatusCode();

        if (! ErrorStatus::isError($status)
            || $request->expectsJson()
            || $request->is('admin', 'admin/*', 'file/*', 'apps/*', 'front/*', 'up')
            || ($status >= 500 && config('app.debug'))) {
            return $response;
        }

        try {
            $segment = $request->segment(1);
            $lang = in_array($segment, Setting::selectedLanguages(), true) ? $segment : Setting::defaultLanguage();
            App::setLocale($lang);
            $key = ErrorStatus::textKey($status);
            $siteName = Setting::siteName();

            return Inertia::render('Front/Error', [
                'status' => $status,
                'reference' => ErrorStatus::hasReference($status) ? ErrorReference::current() : null,
                'homeUrl' => FrontUrl::home($lang),
                // ข้อมูลระบบแบบเบา (เทียบเคียงหน้า Intropage) — useFront() อ่านจาก prop นี้
                'front' => [
                    'lang' => $lang,
                    'languages' => Setting::selectedLanguages(),
                    'defaultLanguage' => Setting::defaultLanguage(),
                    'site' => [
                        'name' => $siteName,
                        'description' => null,
                        'logoUrl' => AppAsset::logo() ? route('app.logo') : null,
                    ],
                    'fontsUrl' => null,
                    't' => trans('front', [], $lang),
                ],
                'seo' => SeoMeta::make($lang, [
                    'title' => (string) trans("front.error_title.{$key}", [], $lang),
                    'canonical' => $request->url(),
                    'noindex' => true,
                ]),
                'accessLog' => ['token' => null],
                'flash' => ['success' => null, 'successId' => null],
            ])->toResponse($request)->setStatusCode($status);
        } catch (Throwable) {
            // สร้างหน้า error ของหน้าบ้านไม่ได้ (เช่น ฐานข้อมูลล่ม) — ใช้หน้าสำรอง (Blade)
            return $response;
        }
    }
}
