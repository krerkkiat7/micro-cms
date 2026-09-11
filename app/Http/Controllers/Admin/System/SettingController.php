<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Setting\UpdateLoginBackSettingRequest;
use App\Http\Requests\Admin\System\Setting\UpdateRecaptchaSettingRequest;
use App\Http\Requests\Admin\System\Setting\UpdateSiteSettingRequest;
use App\Http\Requests\Admin\System\Setting\UpdateSmtpSettingRequest;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /** ชื่อกลุ่มภาษาไทย — ใช้แสดงผล log และข้อความแจ้งเตือน */
    private const GROUP_LABELS = [
        'site' => 'ข้อมูลระบบ',
        'smtp' => 'SMTP',
        'recaptcha' => 'reCAPTCHA',
        'login_back' => 'การเข้าสู่ระบบหลังบ้าน',
    ];

    /**
     * หน้าตั้งค่าระบบ — ฟอร์มแยกกลุ่มตาม Setting::GROUPS
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ตั้งค่าระบบ');
        }

        $settings = collect(Setting::GROUPS)
            ->mapWithKeys(fn (string $group) => [
                $group => SysSetting::query()->where('group', $group)->pluck('value', 'name'),
            ]);

        return Inertia::render('Admin/System/Setting/Index', [
            'settings' => $settings,
        ]);
    }

    public function updateSite(UpdateSiteSettingRequest $request): RedirectResponse
    {
        return $this->saveGroup($request, 'site');
    }

    public function updateSmtp(UpdateSmtpSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // ไม่มีการ auth — เคลียร์ username/password แม้ client จะส่งมาก็ตาม
        if ($data['use_auth'] !== 'Y') {
            $data['username'] = null;
            $data['password'] = null;
        }

        return $this->saveGroup($request, 'smtp', $data);
    }

    public function updateRecaptcha(UpdateRecaptchaSettingRequest $request): RedirectResponse
    {
        return $this->saveGroup($request, 'recaptcha');
    }

    public function updateLoginBack(UpdateLoginBackSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // ไม่กำหนดจำนวนครั้งที่ผิดพลาด — เคลียร์ lockout_count แม้ client จะส่งมาก็ตาม
        if ($data['lockout_enabled'] !== 'Y') {
            $data['lockout_count'] = null;
        }

        return $this->saveGroup($request, 'login_back', $data);
    }

    /**
     * บันทึกค่ากลุ่มเดียว — ลบแถวเดิมของกลุ่มนั้นจริง (ไม่ใช่ soft delete) แล้ว insert ใหม่ทั้งหมด
     */
    private function saveGroup(FormRequest $request, string $group, ?array $data = null): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        $data ??= $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($group, $data, $userId) {
            SysSetting::query()->where('group', $group)->forceDelete();

            foreach ($data as $name => $value) {
                SysSetting::create([
                    'group' => $group,
                    'name' => $name,
                    'value' => $value !== null ? (string) $value : null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        });

        Setting::forget($group);

        LogBackAction::record('system.setting', 'update', self::GROUP_LABELS[$group]);

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * หน้าล้างแคช
     */
    public function clearcache(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        LogBackAccess::record('ล้างแคช - ตั้งค่าระบบ');

        return Inertia::render('Admin/System/Setting/ClearCache');
    }

    /**
     * ล้างแคชของตั้งค่ากลุ่มเดียว
     */
    public function clearCacheGroup(Request $request, string $group): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (! in_array($group, Setting::GROUPS, true)) {
            abort(404);
        }

        Setting::forget($group);

        LogBackAction::record('system.setting.cache', 'clear', self::GROUP_LABELS[$group]);

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชของตั้งค่าทุกกลุ่ม
     */
    public function clearCacheAll(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forgetAll();

        LogBackAction::record('system.setting.cache', 'clear', 'ทั้งหมด');

        return back()->with('success', 'ล้างแคชทั้งหมดเรียบร้อยแล้ว');
    }
}
