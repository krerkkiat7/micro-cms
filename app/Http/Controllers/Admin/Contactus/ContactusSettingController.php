<?php

namespace App\Http\Controllers\Admin\Contactus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Contactus\UpdateContactusSettingRequest;
use App\Models\FileInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\ContactusSetting;
use App\Support\Front\FrontCache;
use App\Support\GoogleMap;
use App\Support\PageTextStyle;
use App\Support\Setting;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ตั้งค่าโมดูลติดต่อเรา (sys_setting group = "contactus") + หน้าล้างแคช — ตรวจสิทธิ์ด้วย contactus.setting.manage เดียว
 * log action module_code = "contactus.setting" (update = บันทึกตั้งค่า, clear = ล้างแคช)
 */
class ContactusSettingController extends Controller
{
    /**
     * หน้าตั้งค่าโมดูลติดต่อเรา — ทะเบียนคีย์/ค่าเริ่มต้นอยู่ที่ App\Support\ContactusSetting
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ตั้งค่าโมดูลติดต่อเรา');
        }

        $settings = ContactusSetting::all();

        return Inertia::render('Admin/Contactus/Setting/Index', [
            'settings' => $settings,
            'mapImageFile' => $this->fileToArray($settings['map_image_id'] ?: null),
            'fonts' => PageTextStyle::fontNames(),
            'fontsUrl' => PageTextStyle::fontsStylesheetUrl(),
            // ข้อมูลติดต่อจริงมาจากตั้งค่าระบบ — ส่งไปแสดงเป็นตัวอย่าง/บอกว่ายังไม่ได้กรอกช่องไหน
            'contact' => [
                'owner' => Setting::get('site', 'copyright_owner') ?? Setting::siteName(),
                'address' => Setting::get('contact', 'address_'.Setting::defaultLanguage()),
                'phone' => Setting::get('contact', 'phone'),
                'fax' => Setting::get('contact', 'fax'),
                'mobile' => Setting::get('contact', 'mobile'),
                'email' => Setting::get('contact', 'email'),
            ],
            'turnstileConfigured' => Turnstile::configured(),
            'googleMapKeySet' => GoogleMap::apiKey() !== null,
            'canManageSystemSetting' => $request->user()->hasPermission('system.setting.manage'),
        ]);
    }

    /**
     * บันทึกตั้งค่า — ลบแถวเดิมของกลุ่ม contactus จริงแล้ว insert ใหม่ทั้งหมด (เหมือน PopupSettingController::update())
     */
    public function update(UpdateContactusSettingRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        $data = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $userId) {
            SysSetting::query()->where('group', 'contactus')->forceDelete();

            foreach ($data as $name => $value) {
                SysSetting::create([
                    'group' => 'contactus',
                    'name' => $name,
                    'value' => $value !== null ? (string) $value : null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        });

        Setting::forget('contactus');

        LogBackAction::record('contactus.setting', 'update', 'ตั้งค่าโมดูลติดต่อเรา');

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * หน้าล้างแคชของโมดูลติดต่อเรา
     */
    public function clearcache(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        LogBackAccess::record('ล้างแคช - ติดต่อเรา');

        return Inertia::render('Admin/Contactus/Setting/ClearCache');
    }

    /**
     * ล้างแคชของตั้งค่าโมดูลติดต่อเรา
     */
    public function clearCacheSetting(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('contactus');

        LogBackAction::record('contactus.setting', 'clear', 'แคชตั้งค่าติดต่อเรา');

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชหน้าติดต่อเราที่แสดงหน้าบ้าน
     */
    public function clearCacheFront(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        FrontCache::forgetAll();

        LogBackAction::record('contactus.setting', 'clear', 'แคชหน้าติดต่อเราที่แสดงหน้าบ้าน');

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชทั้งหมดของโมดูลติดต่อเรา (ตั้งค่า + หน้าบ้าน — Setting::forget() ล้างแคชหน้าบ้านให้ด้วย)
     */
    public function clearCacheAll(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('contactus.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('contactus');

        LogBackAction::record('contactus.setting', 'clear', 'แคชทั้งหมด');

        return back()->with('success', 'ล้างแคชทั้งหมดเรียบร้อยแล้ว');
    }

    /**
     * แปลง id ของ file_info เป็น array แบบเดียวกับที่ FilePickerField.vue ใช้แสดงผล (เหมือน SettingController)
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
}
