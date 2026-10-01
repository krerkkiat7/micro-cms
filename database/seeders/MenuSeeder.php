<?php

namespace Database\Seeders;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\SysMenu;
use App\Models\SysMenuGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * ข้อมูลเมนูหลังบ้าน (sys_menu_group + sys_menu)
 *
 * icon = ชื่อไอคอน lucide แบบ PascalCase (ดูรายการที่รองรับใน resources/js/Components/Admin/menuIcons.ts)
 * รันเดี่ยว: php artisan db:seed --class=MenuSeeder
 */
class MenuSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. กลุ่มเมนู — [id, name, icon, sort_order]
        $groups = [
            ['article', 'บทความ', 'Newspaper', 1],
            ['banner', 'ป้ายโฆษณา', 'Image', 2],
            ['popup', 'Popup', 'Megaphone', 3],
            ['intropage', 'Intropage', 'Presentation', 4],
            ['page', 'Page', 'FileText', 5],
            ['contactus', 'Contact Us', 'Mail', 6],
            ['system', 'จัดการระบบ', 'Settings', 99],
        ];

        foreach ($groups as [$id, $name, $icon, $sortOrder]) {
            SysMenuGroup::updateOrCreate(['id' => $id], [
                'name' => $name,
                'icon' => $icon,
                'sort_order' => $sortOrder,
                'status' => 'Y',
            ]);
        }

        // 2. เมนูย่อย — [id, group, name, icon, route_name, action_code, sort_order]
        $menus = [
            // module : article
            ['article-category', 'article', 'หมวดหมู่', 'FolderTree', 'admin.article.category.index', 'article.category.view', 1],
            ['article-item', 'article', 'บทความ', 'List', 'admin.article.item.index', 'article.item.view', 2],
            ['article-tag', 'article', 'แท็ก', 'Tags', 'admin.article.tag.index', 'article.item.view', 3],
            ['article-report', 'article', 'รายงาน', 'ChartColumn', 'admin.article.report.index', 'article.report.view', 4],
            ['article-setting', 'article', 'ตั้งค่า', 'SlidersHorizontal', 'admin.article.setting.index', 'article.setting.manage', 99],

            // module : banner
            ['banner-category', 'banner', 'หมวดหมู่', 'FolderTree', 'admin.banner.category.index', 'banner.category.view', 1],
            ['banner-item', 'banner', 'ป้ายโฆษณา', 'List', 'admin.banner.item.index', 'banner.item.view', 2],
            ['banner-report', 'banner', 'รายงาน', 'ChartColumn', 'admin.banner.report.index', 'banner.report.view', 3],
            ['banner-setting', 'banner', 'ตั้งค่า', 'SlidersHorizontal', 'admin.banner.setting.index', 'banner.setting.manage', 99],

            // module : popup
            ['popup-item', 'popup', 'Popup', 'List', 'admin.popup.item.index', 'popup.item.view', 1],
            ['popup-setting', 'popup', 'ตั้งค่า', 'SlidersHorizontal', 'admin.popup.setting.index', 'popup.setting.manage', 99],

            // module : intropage
            ['intropage-item', 'intropage', 'Intropage', 'List', 'admin.intropage.item.index', 'intropage.item.view', 1],

            // module : page
            ['page-item', 'page', 'หน้าเพจ', 'List', 'admin.page.item.index', 'page.item.view', 1],
            ['page-report', 'page', 'รายงาน', 'ChartColumn', 'admin.page.report.index', 'page.report.view', 2],

            // module : contactus
            ['contactus-item', 'contactus', 'ติดต่อเรา', 'List', 'admin.contactus.item.index', 'contactus.item.view', 1],
            ['contactus-setting', 'contactus', 'ตั้งค่า', 'SlidersHorizontal', 'admin.contactus.setting.index', 'contactus.setting.manage', 99],

            // จัดการระบบ
            ['system-user', 'system', 'จัดการผู้ใช้งาน', 'Users', 'admin.system.user.index', 'system.user.view', 1],
            ['system-usergroup', 'system', 'จัดการกลุ่มผู้ใช้งาน', 'Shield', 'admin.system.usergroup.index', 'system.usergroup.view', 2],
            ['system-menu', 'system', 'จัดการเมนู', 'ListTree', 'admin.system.menu.index', 'system.menu.view', 4],
            ['system-template', 'system', 'จัดการ Template', 'LayoutTemplate', 'admin.system.template.index', 'system.template.view', 5],
            ['system-back-log-access', 'system', 'ประวัติการใช้งาน - หลังบ้าน', 'History', 'admin.system.backlog.access.index', 'system.backlog.access', 11],
            ['system-back-log-action', 'system', 'ประวัติการกระทำ - หลังบ้าน', 'History', 'admin.system.backlog.action.index', 'system.backlog.action', 12],
            ['system-back-log-login', 'system', 'ประวัติการเข้าสู่ระบบ - หลังบ้าน', 'History', 'admin.system.backlog.login.index', 'system.backlog.login', 13],
            ['system-front-log-access', 'system', 'ประวัติการใช้งาน - หน้าบ้าน', 'History', 'admin.system.frontlog.access.index', 'system.frontlog.access', 14],
            // ['system-front-log-action', 'system', 'ประวัติการกระทำ - หน้าบ้าน', 'History', 'admin.system.frontlog.action.index', 'system.frontlog.action', 15],
            // ['system-front-log-login', 'system', 'ประวัติการเข้าสู่ระบบ - หน้าบ้าน', 'History', 'admin.system.frontlog.login.index', 'system.frontlog.login', 16],
            ['system-setting', 'system', 'ตั้งค่าระบบ', 'SlidersHorizontal', 'admin.system.setting.index', 'system.setting.manage', 99],
            ['system-errorviewer', 'system', 'ตรวจสอบ Error', 'Bug', 'admin.system.errorviewer.index', 'system.error.view', 100],
        ];

        // "จัดการไฟล์" เปลี่ยนมาเป็นลิงก์ hardcode ใน AppSidebar.vue (ต่อจากโปรไฟล์ เหมือน Dashboard/Profile)
        // ไม่ผ่าน sys_menu แล้ว — ลบ record เดิมที่เคย seed ไว้ (id 'system-file') ออกจาก DB จริงด้วย
        // เพื่อให้ seeder รันซ้ำแล้วไม่มี row ค้าง
        SysMenu::where('id', 'system-file')->forceDelete();

        // ประวัติการกระทำ/การเข้าสู่ระบบ - หน้าบ้าน ยังไม่มีหน้าจอ (รอ login หน้าบ้าน) — ลบเมนูที่เคย seed ไว้ออก
        SysMenu::whereIn('id', ['system-front-log-action', 'system-front-log-login'])->forceDelete();

        foreach ($menus as [$id, $groupId, $name, $icon, $routeName, $actionCode, $sortOrder]) {
            SysMenu::updateOrCreate(['id' => $id], [
                'menu_group_id' => $groupId,
                'name' => $name,
                'icon' => $icon,
                'route_name' => $routeName,
                'sort_order' => $sortOrder,
                'action_code' => $actionCode,
                'status' => 'Y',
            ]);
        }

        // ให้ sidebar หลังบ้านที่ cache ไว้ (HandleInertiaRequests::adminMenu) โหลดเมนูชุดใหม่
        Cache::forever(HandleInertiaRequests::MENU_VERSION_KEY, (int) Cache::get(HandleInertiaRequests::MENU_VERSION_KEY, 1) + 1);
    }
}
