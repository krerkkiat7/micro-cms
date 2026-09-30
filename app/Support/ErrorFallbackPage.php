<?php

namespace App\Support;

use App\Support\Admin\AdminErrorPage;
use Throwable;

/**
 * ข้อมูลของหน้า error สำรอง (resources/views/errors/{4xx,5xx}.blade.php) — ใช้เมื่อสร้างหน้า error แบบ Inertia ไม่ได้
 * (ฐานข้อมูลล่ม, ยังไม่ได้ build frontend) หรือ path ที่ไม่ใช่หน้าเว็บ (/file/*, /apps/*)
 *
 * ต้องไม่พึ่งฐานข้อมูล/Vite: ทุกค่าที่อ่านจากตั้งค่าระบบห่อ try/catch แล้วใช้ค่าสำรอง (ชื่อจาก APP_NAME, ภาษาจากโฟลเดอร์ lang/)
 * แยกหลังบ้าน (lang/th/error.php) / หน้าบ้าน (ภาษาจากส่วนแรกของ URL, lang/{ภาษา}/front.php) เหมือนหน้าแบบ Inertia
 */
final class ErrorFallbackPage
{
    /**
     * @return array<string, mixed>
     */
    public static function data(int $status): array
    {
        $request = request();
        $isAdmin = AdminErrorPage::isAdminRequest($request);
        $key = ErrorStatus::textKey($status);
        $siteName = self::safe(fn () => Setting::siteName(), (string) config('app.name'));

        if ($isAdmin) {
            $lang = 'th';
            $text = fn (string $name) => (string) trans("error.{$name}", [], $lang);
            $title = (string) trans("error.title.{$key}", [], $lang);
            $description = (string) trans("error.description.{$key}", [], $lang);
            $homeUrl = url('/admin');
        } else {
            $languages = self::safe(fn () => Setting::selectedLanguages(), self::installedLanguages());
            $segment = $request->segment(1);
            $lang = in_array($segment, $languages, true) ? $segment : self::safe(fn () => Setting::defaultLanguage(), $languages[0] ?? 'th');
            $text = fn (string $name) => (string) trans("front.{$name}", [], $lang);
            $title = (string) trans("front.error_title.{$key}", [], $lang);
            $description = (string) trans("front.error_description.{$key}", [], $lang);
            $homeUrl = url('/'.$lang);
        }

        return [
            'lang' => $lang,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'siteName' => $siteName,
            'logoUrl' => url('/apps/logo.png'),
            'homeUrl' => $homeUrl,
            'homeLabel' => $text('back_to_home'),
            'reference' => ErrorStatus::hasReference($status) ? ErrorReference::current() : null,
            'referenceLabel' => $isAdmin ? $text('reference') : $text('error_reference'),
            'referenceHint' => $isAdmin ? $text('reference_hint') : $text('error_reference_hint'),
        ];
    }

    /**
     * @return list<string>
     */
    private static function installedLanguages(): array
    {
        $dirs = glob(lang_path('*'), GLOB_ONLYDIR) ?: [];

        return array_values(array_filter(array_map('basename', $dirs), fn (string $code) => strlen($code) === 2));
    }

    private static function safe(callable $read, mixed $fallback): mixed
    {
        try {
            return $read() ?: $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }
}
