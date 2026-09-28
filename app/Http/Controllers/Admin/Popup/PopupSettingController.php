<?php

namespace App\Http\Controllers\Admin\Popup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Popup\UpdatePopupSettingRequest;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\Front\FrontCache;
use App\Support\PopupSetting;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ตั้งค่าโมดูล popup (sys_setting group = "popup") + หน้าล้างแคช — ตรวจสิทธิ์ด้วย popup.setting.manage เดียว
 * log action module_code = "popup.setting" (update = บันทึกตั้งค่า, clear = ล้างแคช)
 */
class PopupSettingController extends Controller
{
    /**
     * หน้าตั้งค่าโมดูล popup — ทะเบียนคีย์/ค่าเริ่มต้นอยู่ที่ App\Support\PopupSetting
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ตั้งค่าโมดูล Popup');
        }

        return Inertia::render('Admin/Popup/Setting/Index', [
            'settings' => PopupSetting::all(),
        ]);
    }

    /**
     * บันทึกตั้งค่า — ลบแถวเดิมของกลุ่ม popup จริงแล้ว insert ใหม่ทั้งหมด (เหมือน ArticleSettingController::update())
     */
    public function update(UpdatePopupSettingRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        $data = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $userId) {
            SysSetting::query()->where('group', 'popup')->forceDelete();

            foreach ($data as $name => $value) {
                SysSetting::create([
                    'group' => 'popup',
                    'name' => $name,
                    'value' => $value !== null ? (string) $value : null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        });

        Setting::forget('popup');

        LogBackAction::record('popup.setting', 'update', 'ตั้งค่าโมดูล Popup');

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * หน้าล้างแคชของโมดูล popup
     */
    public function clearcache(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        LogBackAccess::record('ล้างแคช - Popup');

        return Inertia::render('Admin/Popup/Setting/ClearCache');
    }

    /**
     * ล้างแคชของตั้งค่าโมดูล popup
     */
    public function clearCacheSetting(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('popup');

        LogBackAction::record('popup.setting', 'clear', 'แคชตั้งค่า Popup');

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคช popup ที่แสดงหน้าบ้าน
     */
    public function clearCacheFront(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        FrontCache::forgetAll();

        LogBackAction::record('popup.setting', 'clear', 'แคช Popup ที่แสดงหน้าบ้าน');

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชทั้งหมดของโมดูล popup (ตั้งค่า + หน้าบ้าน — Setting::forget() ล้างแคชหน้าบ้านให้ด้วย)
     */
    public function clearCacheAll(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('popup.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('popup');

        LogBackAction::record('popup.setting', 'clear', 'แคชทั้งหมด');

        return back()->with('success', 'ล้างแคชทั้งหมดเรียบร้อยแล้ว');
    }
}
