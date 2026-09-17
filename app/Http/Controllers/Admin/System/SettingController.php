<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\Setting\TestSmtpSettingRequest;
use App\Http\Requests\Admin\System\Setting\UpdateLoginBackSettingRequest;
use App\Http\Requests\Admin\System\Setting\UpdateSiteSettingRequest;
use App\Http\Requests\Admin\System\Setting\UpdateSmtpSettingRequest;
use App\Http\Requests\Admin\System\Setting\UpdateTurnstileSettingRequest;
use App\Mail\TestSmtpMail;
use App\Models\FileInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\AppAsset;
use App\Support\FileCache;
use App\Support\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SettingController extends Controller
{
    /** ชื่อกลุ่มภาษาไทย — ใช้แสดงผล log และข้อความแจ้งเตือน (รวมทุกกลุ่มที่ลงทะเบียนใน Setting::GROUPS แม้จะไม่ใช่ของหน้านี้เอง) */
    private const GROUP_LABELS = [
        'site' => 'ข้อมูลระบบ',
        'smtp' => 'SMTP',
        'turnstile' => 'Turnstile',
        'login_back' => 'การเข้าสู่ระบบหลังบ้าน',
        'article' => 'โมดูลบทความ',
    ];

    /**
     * กลุ่มตั้งค่าที่หน้านี้ (ตั้งค่าระบบ) มีฟอร์มให้แก้ไขเอง — ไม่ใช่ทุกกลุ่มใน Setting::GROUPS เพราะกลุ่มอื่น
     * (เช่น 'article') เป็นของโมดูลนั้น ๆ ที่มีหน้าตั้งค่าแยกของตัวเอง แค่มาลงทะเบียนแคชร่วมทะเบียนเดียวกัน
     */
    private const OWN_GROUPS = ['site', 'smtp', 'turnstile', 'login_back'];

    /**
     * หน้าตั้งค่าระบบ — ฟอร์มแยกกลุ่มตาม self::OWN_GROUPS
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ตั้งค่าระบบ');
        }

        $settings = collect(self::OWN_GROUPS)
            ->mapWithKeys(fn (string $group) => [
                $group => SysSetting::query()->where('group', $group)->pluck('value', 'name'),
            ]);

        return Inertia::render('Admin/System/Setting/Index', [
            'settings' => $settings,
            'logoFile' => $this->fileToArray($settings->get('site')?->get('logo_id')),
            'faviconFile' => $this->fileToArray($settings->get('site')?->get('favicon_id')),
        ]);
    }

    /**
     * แปลง id ของ file_info เป็น array แบบเดียวกับที่ Components/Admin/FileManager/FilePickerField.vue
     * ใช้แสดงผล (ดู ProfileController::edit()) — คืน null ถ้าไม่มีค่า/ไฟล์ถูกลบไปแล้ว
     *
     * @return array<string, mixed>|null
     */
    private function fileToArray(?string $fileId): ?array
    {
        $file = $fileId ? FileInfo::find($fileId) : null;

        return $file ? [
            'id' => $file->id,
            'name' => $file->name,
            'hash_name' => $file->hash_name,
            'extension' => $file->extension,
            'file_size' => $file->file_size,
            'is_image' => $file->isImage(),
            'created_at' => $file->created_at,
        ] : null;
    }

    public function updateSite(UpdateSiteSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // เก็บภาษาที่เลือกรวมเป็น 1 record (คั่นด้วย ,) ไม่แยกเก็บทีละภาษา — รองรับเพิ่มภาษาในอนาคตได้
        // โดยไม่ต้องแก้ schema (ดู App\Support\Setting::selectedLanguages())
        $data['lang_selected'] = implode(',', $data['lang_selected']);

        return $this->saveGroup($request, 'site', $data);
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

    /**
     * ทดสอบส่งอีเมลด้วยการตั้งค่า SMTP ที่ "บันทึกไว้แล้ว" ใน sys_setting (ไม่ใช่ค่าที่พิมพ์ค้างในฟอร์ม
     * ที่ยังไม่กดบันทึก) — App\Providers\AppServiceProvider::applySmtpSetting() ผูก mail.mailers.smtp.*
     * จาก Setting::group('smtp') ให้ทุก request อยู่แล้ว จึงแค่เรียก Mail::raw() ตรง ๆ ได้เลย
     */
    public function testSmtp(TestSmtpSettingRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        // ยังไม่เคยบันทึก host เลย mail.default จะยังเป็น 'log' (เขียนลง log เฉย ๆ ไม่ได้ส่งจริง)
        // ซึ่งจะ "สำเร็จ" เสมอ ทั้งที่ไม่ได้ทดสอบอะไรจริง — กันไว้ก่อนด้วยการเช็ก host ให้ชัดเจน
        if (! Setting::get('smtp', 'host')) {
            throw ValidationException::withMessages([
                'send' => 'ยังไม่ได้บันทึกการตั้งค่า SMTP (Host) กรุณาบันทึกการตั้งค่าก่อนทดสอบส่งอีเมล',
            ]);
        }

        $data = $request->validated();

        try {
            Mail::to($data['to'])->send(new TestSmtpMail($data['subject'], $data['body']));
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'send' => 'ส่งอีเมลไม่สำเร็จ: '.$e->getMessage(),
            ]);
        }

        LogBackAction::record('system.setting.smtp', 'test', $data['to']);

        return back()->with('success', 'ส่งอีเมลทดสอบเรียบร้อยแล้ว กรุณาตรวจสอบใน Inbox และ Junk/Spam Box');
    }

    public function updateTurnstile(UpdateTurnstileSettingRequest $request): RedirectResponse
    {
        return $this->saveGroup($request, 'turnstile');
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

        if ($group === 'site') {
            AppAsset::forgetCache(); // logo_id/favicon_id อยู่ในกลุ่มนี้ — เคลียร์คู่กันเสมอ
        }

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

        if ($group === 'site') {
            AppAsset::forgetCache();
        }

        LogBackAction::record('system.setting.cache', 'clear', self::GROUP_LABELS[$group]);

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชของตั้งค่าทุกกลุ่มที่ลงทะเบียนไว้ใน Setting::GROUPS — รวมกลุ่มของโมดูลอื่น (เช่น 'article')
     * ที่มาลงทะเบียนร่วมทะเบียนเดียวกันด้วย ไม่ได้จำกัดแค่ self::OWN_GROUPS
     */
    public function clearCacheAll(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forgetAll();
        AppAsset::forgetCache();

        LogBackAction::record('system.setting.cache', 'clear', 'ทั้งหมด');

        return back()->with('success', 'ล้างแคชทั้งหมดเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชของโมดูลจัดการไฟล์ (ผลการค้นหา file_info ด้วย hash_name) — driver cache เป็น
     * `database` ไม่รองรับ tag จึง flush ทั้ง cache store (ดู App\Support\FileCache::forgetAll())
     */
    public function clearCacheFiles(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('system.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        FileCache::forgetAll();

        LogBackAction::record('system.setting.cache', 'clear', 'ไฟล์ทั้งหมด');

        return back()->with('success', 'ล้างแคชไฟล์เรียบร้อยแล้ว');
    }
}
