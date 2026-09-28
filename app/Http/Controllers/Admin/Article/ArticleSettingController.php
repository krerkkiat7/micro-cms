<?php

namespace App\Http\Controllers\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Article\UpdateArticleSettingRequest;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\ArticleSetting;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ตั้งค่าโมดูลบทความ (sys_setting group = "article") — ต่างจากตั้งค่าระบบ (Admin\System\SettingController)
 * ที่แยกฟอร์ม/ปุ่มบันทึกต่อกลุ่ม หน้านี้รวมทุกกลุ่มไว้ในฟอร์มเดียว ปุ่มบันทึกอยู่นอกกล่องกลุ่มทั้งหมด (เหมือน
 * ฟอร์มเพิ่ม/แก้ไขบทความทั่วไป) ตรวจสิทธิ์ + log ทุก action ด้วย article.setting.manage เดียว (ไม่มี .view แยก)
 * log action module_code = "article.setting" / "article.setting.cache"
 */
class ArticleSettingController extends Controller
{
    /**
     * หน้าตั้งค่าโมดูลบทความ — กลุ่ม "รายการบทความ" (+ กลุ่มย่อยการแสดงแบบการ์ด/แถว) และ "รายละเอียดบทความ"
     * บันทึกร่วมฟอร์มเดียว ทะเบียนคีย์/ค่าเริ่มต้นอยู่ที่ App\Support\ArticleSetting
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        if (count($request->query()) === 0) {
            LogBackAccess::record('ตั้งค่าโมดูลบทความ');
        }

        return Inertia::render('Admin/Article/Setting/Index', [
            // ค่าที่บันทึกไว้ทับค่าเริ่มต้น — คีย์ที่เพิ่มทีหลัง (ยังไม่เคยบันทึก) ได้ค่าเริ่มต้นเสมอ
            'settings' => ArticleSetting::all(),
        ]);
    }

    /**
     * บันทึกตั้งค่าโมดูลบทความ — ลบแถวเดิมของกลุ่ม article จริง (ไม่ soft delete) แล้ว insert ใหม่ทั้งหมด
     * เหมือน SettingController::saveGroup() แต่รวมทุกฟิลด์ไว้ในคำขอเดียว ไม่แยกกลุ่มต่อ endpoint
     */
    public function update(UpdateArticleSettingRequest $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        $data = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $userId) {
            SysSetting::query()->where('group', 'article')->forceDelete();

            foreach ($data as $name => $value) {
                SysSetting::create([
                    'group' => 'article',
                    'name' => $name,
                    'value' => $value !== null ? (string) $value : null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        });

        Setting::forget('article');

        LogBackAction::record('article.setting', 'update', 'ตั้งค่าโมดูลบทความ');

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * หน้าล้างแคชของโมดูลบทความ
     */
    public function clearcache(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->hasPermission('article.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        LogBackAccess::record('ล้างแคช - บทความ');

        return Inertia::render('Admin/Article/Setting/ClearCache');
    }

    /**
     * ล้างแคชของตั้งค่าโมดูลบทความ
     */
    public function clearCacheSetting(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('article');

        LogBackAction::record('article.setting.cache', 'clear', 'ตั้งค่าบทความ');

        return back()->with('success', 'ล้างแคชเรียบร้อยแล้ว');
    }

    /**
     * ล้างแคชทั้งหมดของโมดูลบทความ — ตอนนี้มีแคชเดียว (ตั้งค่า) อนาคตถ้าเพิ่มแคชอื่นของโมดูลนี้
     * (เช่น รายละเอียดบทความ/รายการบทความที่ใช้ในหน้าบ้าน) ให้เพิ่มการล้างแคชนั้นในเมธอดนี้ด้วย
     */
    public function clearCacheAll(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermission('article.setting.manage')) {
            return redirect()->route('admin.dashboard');
        }

        Setting::forget('article');

        LogBackAction::record('article.setting.cache', 'clear', 'ทั้งหมด');

        return back()->with('success', 'ล้างแคชทั้งหมดเรียบร้อยแล้ว');
    }
}
