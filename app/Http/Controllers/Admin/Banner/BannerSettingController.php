<?php

namespace App\Http\Controllers\Admin\Banner;

use App\Http\Controllers\Controller;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Support\Front\FrontCache;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ตั้งค่าโมดูลป้ายโฆษณา — ยังไม่มีฟิลด์ตั้งค่าจริง หน้าตั้งค่า (admin.banner.setting.index) จึงเป็นหน้าล้างแคชของโมดูลนี้โดยตรง
 * แคชของโมดูล: ตั้งค่า (sys_setting group = "banner") และแคชหน้าบ้านที่แสดงป้ายโฆษณา (widget Slideshow/Slideset/Grid ใน FrontCache)
 * ตรวจสิทธิ์ banner.setting.manage ทุก action; log action module_code = "banner.setting.cache"
 * ถ้าเพิ่มฟิลด์ตั้งค่าจริงในอนาคต ให้แยกหน้าตั้งค่า + แท็บล้างแคช ตาม pattern Admin\Article\ArticleSettingController
 */
class BannerSettingController extends Controller
{
    /**
     * หน้าล้างแคชของโมดูลป้ายโฆษณา
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ล้างแคช - ป้ายโฆษณา');
        }

        return Inertia::render('Admin/Banner/Setting/Index');
    }

    /**
     * ล้างแคชของตั้งค่าโมดูลป้ายโฆษณา
     */
    public function clearCacheSetting(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('banner');

        LogBackAction::record('banner.setting.cache', 'clear', 'ตั้งค่าป้ายโฆษณา');

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชหน้าบ้าน — ป้ายโฆษณาที่แสดงผ่าน widget ของหน้าเพจ (แคชหน้าบ้านเป็นชุดเดียวกันทั้งไซต์ ล้างทั้งหมด)
     */
    public function clearCacheFront(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        FrontCache::forgetAll();

        LogBackAction::record('banner.setting.cache', 'clear', 'ป้ายโฆษณาที่แสดงหน้าบ้าน');

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชทั้งหมดของโมดูลป้ายโฆษณา (ตั้งค่า + หน้าบ้าน — Setting::forget() ล้างแคชหน้าบ้านให้ด้วย)
     */
    public function clearCacheAll(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('banner.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('banner');

        LogBackAction::record('banner.setting.cache', 'clear', 'ทั้งหมด');

        return back()->with('success', 'ล้างแคชทั้งหมดเรียบร้อยแล้ว');
    }
}
