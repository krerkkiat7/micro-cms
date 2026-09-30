<?php

namespace Database\Seeders;

use App\Models\SysAction;
use App\Models\SysActionGroup;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // กลุ่มสิทธิ์
        $groups = [
            ['id' => 'article', 'name' => 'บทความ', 'sort_order' => 1],
            ['id' => 'banner', 'name' => 'ป้ายโฆษณา', 'sort_order' => 2],
            ['id' => 'popup', 'name' => 'Popup', 'sort_order' => 3],
            ['id' => 'intropage', 'name' => 'Intropage', 'sort_order' => 4],
            ['id' => 'page', 'name' => 'Page', 'sort_order' => 5],
            ['id' => 'contactus', 'name' => 'Contact Us', 'sort_order' => 6],

            ['id' => 'system', 'name' => 'จัดการระบบ', 'sort_order' => 99],
        ];

        foreach ($groups as $group) {
            SysActionGroup::updateOrCreate(['id' => $group['id']], $group + ['status' => 'Y']);
        }

        // สิทธิ์ — [id, action_group_id, parent_id, code, name, sort_order]
        $actions = [
            // module : article
            ['article001', 'article', null, 'article.category.view', 'แสดงหมวดหมู่', 1],
            ['article002', 'article', 'article001', 'article.category.manage', 'เพิ่ม/แก้ไขหมวดหมู่', 1],
            ['article003', 'article', 'article002', 'article.category.delete', 'ลบหมวดหมู่', 1],
            ['article101', 'article', null, 'article.item.view', 'แสดงบทความ', 2],
            ['article102', 'article', 'article101', 'article.item.manage', 'เพิ่ม/แก้ไขบทความ', 1],
            ['article103', 'article', 'article102', 'article.item.delete', 'ลบบทความ', 1],
            ['article201', 'article', null, 'article.report.view', 'แสดงรายงาน', 3],
            ['article901', 'article', null, 'article.setting.manage', 'ตั้งค่า', 99],

            // module : banner
            ['banner001', 'banner', null, 'banner.category.view', 'แสดงหมวดหมู่', 1],
            ['banner002', 'banner', 'banner001', 'banner.category.manage', 'เพิ่ม/แก้ไขหมวดหมู่', 1],
            ['banner003', 'banner', 'banner002', 'banner.category.delete', 'ลบหมวดหมู่', 1],
            ['banner101', 'banner', null, 'banner.item.view', 'แสดงป้ายโฆษณา', 2],
            ['banner102', 'banner', 'banner101', 'banner.item.manage', 'เพิ่ม/แก้ไขป้ายโฆษณา', 1],
            ['banner103', 'banner', 'banner102', 'banner.item.delete', 'ลบป้ายโฆษณา', 1],
            ['banner201', 'banner', null, 'banner.report.view', 'แสดงรายงาน', 3],
            ['banner901', 'banner', null, 'banner.setting.manage', 'ตั้งค่า', 99],

            // module : popup
            ['popup001', 'popup', null, 'popup.item.view', 'แสดง Popup', 1],
            ['popup002', 'popup', 'popup001', 'popup.item.manage', 'เพิ่ม/แก้ไข Popup', 1],
            ['popup003', 'popup', 'popup002', 'popup.item.delete', 'ลบ Popup', 1],
            ['popup901', 'popup', null, 'popup.setting.manage', 'ตั้งค่า', 99],

            // module : intropage
            ['intropage001', 'intropage', null, 'intropage.item.view', 'แสดง Intropage', 1],
            ['intropage002', 'intropage', 'intropage001', 'intropage.item.manage', 'เพิ่ม/แก้ไข Intropage', 1],
            ['intropage003', 'intropage', 'intropage002', 'intropage.item.delete', 'ลบ Intropage', 1],

            // module : page
            ['page001', 'page', null, 'page.item.view', 'แสดงเพจ', 1],
            ['page002', 'page', 'page001', 'page.item.manage', 'เพิ่ม/แก้ไขเพจ', 1],
            ['page003', 'page', 'page002', 'page.item.delete', 'ลบเพจ', 1],
            ['page101', 'page', null, 'page.report.view', 'แสดงรายงาน', 2],

            // module : contactus
            ['contactus001', 'contactus', null, 'contactus.item.view', 'แสดงติดต่อเรา', 1],
            ['contactus002', 'contactus', 'contactus001', 'contactus.item.manage', 'เพิ่ม/แก้ไขติดต่อเรา', 1],
            ['contactus901', 'contactus', null, 'contactus.setting.manage', 'ตั้งค่า', 99],

            // จัดการระบบ
            ['system001', 'system', null, 'system.user.view', 'แสดงผู้ใช้งาน', 1],
            ['system002', 'system', 'system001', 'system.user.manage', 'เพิ่ม/แก้ไขผู้ใช้งาน', 1],
            ['system003', 'system', 'system002', 'system.user.delete', 'ลบผู้ใช้งาน', 1],
            ['system004', 'system', 'system002', 'system.user.password', 'รีเซ็ตรหัสผ่าน', 2],
            ['system011', 'system', null, 'system.usergroup.view', 'แสดงกลุ่มผู้ใช้งาน', 2],
            ['system012', 'system', 'system011', 'system.usergroup.manage', 'เพิ่ม/แก้ไขกลุ่มผู้ใช้งาน', 1],
            ['system013', 'system', 'system012', 'system.usergroup.delete', 'ลบกลุ่มผู้ใช้งาน', 1],
            ['system014', 'system', 'system012', 'system.usergroup.rights', 'กำหนดสิทธิ์', 2],
            ['system021', 'system', null, 'system.menu.view', 'แสดงเมนู', 3],
            ['system022', 'system', 'system021', 'system.menu.manage', 'เพิ่ม/แก้ไขเมนู', 1],
            ['system023', 'system', 'system022', 'system.menu.delete', 'ลบเมนู', 1],
            ['system031', 'system', null, 'system.template.view', 'แสดง Template', 4],
            ['system032', 'system', 'system031', 'system.template.manage', 'เพิ่ม/แก้ไข Template', 1],
            ['system033', 'system', 'system032', 'system.template.delete', 'ลบ Template', 1],
            ['system101', 'system', null, 'system.backlog.access', 'แสดงประวัติการใช้งาน - หลังบ้าน', 11],
            ['system102', 'system', null, 'system.backlog.action', 'แสดงประวัติการกระทำ - หลังบ้าน', 12],
            ['system103', 'system', null, 'system.backlog.login', 'แสดงประวัติการเข้าสู่ระบบ - หลังบ้าน', 13],
            ['system104', 'system', null, 'system.frontlog.access', 'แสดงประวัติการใช้งาน - หน้าบ้าน', 14],
            // ['system105', 'system', null, 'system.frontlog.action', 'แสดงประวัติการกระทำ - หน้าบ้าน', 15],
            // ['system106', 'system', null, 'system.frontlog.login', 'แสดงประวัติการเข้าสู่ระบบ - หน้าบ้าน', 16],
            ['system908', 'system', null, 'system.file.manage', 'จัดการไฟล์', 91],
            ['system909', 'system', null, 'system.setting.manage', 'ตั้งค่าระบบ', 92],
            ['system910', 'system', null, 'system.error.view', 'ตรวจสอบ Error', 93],

        ];

        // เอาสิทธิ์ article.tag.* ที่เคยแยกไว้ต่างหากออก — รวมเข้ากับ article.item.* แทน
        // เพราะแท็กสร้างใหม่ได้จากในฟอร์มบทความอยู่แล้ว จึงต้องใช้สิทธิ์ชุดเดียวกัน (cascade ลบ pivot ที่ผูกไว้ด้วย)
        // ลบด้วย code (ไม่ใช่ id) และลบก่อน upsert — id article201 ถูกนำกลับมาใช้กับ article.report.view แล้ว
        SysAction::whereIn('code', ['article.tag.delete', 'article.tag.manage', 'article.tag.view'])->delete();

        // ประวัติการกระทำ/การเข้าสู่ระบบ - หน้าบ้าน ยังไม่มี (รอ login หน้าบ้าน phase ถัดไป) — ลบสิทธิ์ที่เคย seed ไว้ออก
        SysAction::whereIn('code', ['system.frontlog.action', 'system.frontlog.login'])->delete();

        $actionIds = [];
        foreach ($actions as [$id, $actionGroupId, $parentId, $code, $name, $sortOrder]) {
            $actionIds[] = SysAction::updateOrCreate(['id' => $id], [
                'action_group_id' => $actionGroupId,
                'parent_id' => $parentId,
                'code' => $code,
                'name' => $name,
                'sort_order' => $sortOrder,
            ])->id;
        }

        // กลุ่มผู้ใช้ Super Admin (ได้สิทธิ์ทั้งหมด — กลุ่มระบบ แก้ไข/ลบไม่ได้)
        $adminGroup = UserGroup::updateOrCreate(
            ['name' => 'Super Admin'],
            [
                'description' => 'ผู้ดูแลระบบสูงสุด มีสิทธิ์ทุกอย่าง',
                'status' => 'Y',
                'can_edit' => 'N',
                'can_delete' => 'N',
            ],
        );

        $adminGroup->actions()->sync($actionIds);

        // ผู้ใช้สำหรับเข้าสู่ระบบหลังบ้าน (admin@admin.com / password123)
        User::updateOrCreate(
            ['email' => 'admin@admin.com', 'user_type' => 'back'],
            [
                'titlename' => 'คุณ',
                'firstname' => 'System',
                'lastname' => 'Admin',
                'password' => Hash::make('password123'),
                'status' => 'Y',
                'usergroup_id' => $adminGroup->id,
            ],
        );

        // ตัวอย่างการตั้งค่าเว็บไซต์ (กลุ่ม site) — เติมเพิ่มเองได้ภายหลัง
        // ใช้ DB::table()->upsert() ตรง ๆ ไม่ใช่ SysSetting::updateOrCreate() — sys_setting มี primary key
        // แบบ composite (group, name) ไม่มีคอลัมน์ id เอง Eloquent ที่ไม่รู้จัก key นี้จะพัง (WHERE id = ...)
        // ทันทีที่ต้อง UPDATE แถวที่มีอยู่แล้วจริง ๆ (ต่างจากตอน insert ใหม่ที่ไม่มีปัญหา จึงไม่เคยเจอตอนเทส)
        $settings = [
            ['group' => 'site', 'name' => 'site_name', 'value' => 'My CMS'],
            ['group' => 'site', 'name' => 'site_email', 'value' => 'admin@admin.com'],
            ['group' => 'site', 'name' => 'site_description', 'value' => 'Micro-CMS ติดตั้งง่าย ใช้งานง่าย'],
            // ภาษาในระบบ — เก็บรวมเป็น 1 record คั่นด้วย , (ไม่แยกเก็บทีละภาษา) ดู App\Support\Setting::selectedLanguages()
            ['group' => 'site', 'name' => 'lang_selected', 'value' => 'th,en'],
            ['group' => 'site', 'name' => 'lang_default', 'value' => 'th'],
        ];

        DB::table('sys_setting')->upsert(
            array_map(fn (array $s) => $s + ['created_at' => now(), 'updated_at' => now()], $settings),
            ['group', 'name'],
            ['value', 'updated_at'],
        );

        // เมนูหลังบ้าน (ข้อมูลตัวอย่าง — แยกไฟล์)
        $this->call(MenuSeeder::class);

        // หมวดหมู่บทความตัวอย่าง (ข้อมูลตัวอย่าง — แยกไฟล์)
        $this->call(ArticleSeeder::class);

        // หมวดหมู่ป้ายโฆษณาตัวอย่าง (ข้อมูลตัวอย่าง — แยกไฟล์)
        $this->call(BannerSeeder::class);

        // Intropage ตัวอย่าง (ข้อมูลตัวอย่าง — แยกไฟล์)
        $this->call(IntropageSeeder::class);

        // หน้าเพจตัวอย่าง พร้อมโครงสร้างแถว/คอลัมน์/widget (ข้อมูลตัวอย่าง — แยกไฟล์)
        $this->call(PageSeeder::class);

        // เมนูหน้าบ้านตัวอย่าง (ข้อมูลตัวอย่าง — แยกไฟล์ ต้องรันหลัง ArticleSeeder/PageSeeder)
        $this->call(FrontMenuSeeder::class);

        // Template หน้าบ้านตัวอย่าง จากแม่แบบตั้งต้น (ข้อมูลตัวอย่าง — แยกไฟล์)
        $this->call(TemplateSeeder::class);

        // ค่าตั้งต้นของตั้งค่าโมดูลติดต่อเรา (ไม่มีข้อมูลตัวอย่าง — แยกไฟล์)
        $this->call(ContactusSeeder::class);
    }
}
