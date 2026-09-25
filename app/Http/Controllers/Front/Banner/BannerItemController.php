<?php

namespace App\Http\Controllers\Front\Banner;

use App\Http\Controllers\Controller;
use App\Models\BannerItemInfo;
use App\Support\Front\ViewCounter;
use App\Support\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * บันทึกการคลิกลิงก์ของ banner (Slideshow / Slideset / Grid จาก banner) — ยิงจาก navigator.sendBeacon ตอนคลิก
 * (utils/frontPage.ts trackBannerClick) ลิงก์ในหน้าจึงยังเป็น URL จริง ไม่ต้อง redirect ผ่านเซิร์ฟเวอร์
 * ยกเว้น CSRF ไว้ใน bootstrap/app.php (sendBeacon ตั้ง header ไม่ได้) + throttle; นับเฉพาะ banner ที่เปิดใช้งานและมีลิงก์
 * การบันทึกจริง (ข้ามบอท / ไม่นับซ้ำใน session / คิว + batch) อยู่ที่ ViewCounter ประเภท `banner` — ตอบ 204 เสมอ
 */
class BannerItemController extends Controller
{
    public function click(Request $request, ViewCounter $counter): Response
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        $lang = (string) $request->input('lang', '');

        if (! in_array($lang, Setting::selectedLanguages(), true)) {
            $lang = Setting::defaultLanguage();
        }

        if ($id !== false && $id > 0 && $this->clickable($id)) {
            $counter->hit('banner', $id, $lang);
        }

        return response()->noContent();
    }

    private function clickable(int $id): bool
    {
        return BannerItemInfo::query()
            ->whereKey($id)
            ->where('status', 'Y')
            ->whereNotNull('url')
            ->where('url', '<>', '')
            ->exists();
    }
}
