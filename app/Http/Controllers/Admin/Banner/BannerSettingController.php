<?php

namespace App\Http\Controllers\Admin\Banner;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ตั้งค่าโมดูลป้ายโฆษณา — ตอนนี้เป็น placeholder เท่านั้น ยังไม่มีฟิลด์ตั้งค่าจริง (sys_setting group = "banner"
 * ลงทะเบียนไว้ใน App\Support\Setting::GROUPS แล้วเพื่อให้ปุ่มล้างแคชของตั้งค่าระบบครอบคลุมกลุ่มนี้ล่วงหน้า
 * แต่ยังไม่มีแถวข้อมูลจริงให้ล้าง) เมื่อออกแบบฟิลด์ตั้งค่าจริงในอนาคต ให้เพิ่ม update()/clearcache() ตาม
 * pattern Admin\Article\ArticleSettingController
 */
class BannerSettingController extends Controller
{
    /**
     * หน้าตั้งค่าโมดูลป้ายโฆษณา (placeholder)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ตั้งค่าโมดูลป้ายโฆษณา');
        }

        return Inertia::render('Admin/Banner/Setting/Index');
    }
}
