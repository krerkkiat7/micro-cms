<?php

namespace Database\Seeders;

use App\Models\SysMenu;
use App\Models\SysMenuGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างของเมนูหลังบ้าน (sys_menu_group + sys_menu)
 *
 * เป็นข้อมูลจำลองไว้ก่อน — route_name / action_code หลายรายการยังไม่มีจริง
 * รันเดี่ยว: php artisan db:seed --class=MenuSeeder
 */
class MenuSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. กลุ่มเมนู
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
            SysMenuGroup::updateOrCreate(['id' => $group['id']], $group + ['status' => 'Y']);
        }

        // 2. เมนูย่อย — [id, group, name, route_name, action_code, sort_order]
        $menus = [
            // module : article
            ['article-category', 'article', 'หมวดหมู่', 'admin.article.category.index', 'article.category.view', 1],
            ['article-item', 'article', 'บทความ', 'admin.article.item.index', 'article.item.view', 2],
            ['article-setting', 'article', 'ตั้งค่า', 'admin.article.setting.index', 'article.setting.manage', 99],

            // module : banner
            ['banner-category', 'banner', 'หมวดหมู่', 'admin.banner.category.index', 'banner.category.view', 1],
            ['banner-item', 'banner', 'ป้ายโฆษณา', 'admin.banner.item.index', 'banner.item.view', 2],
            ['banner-setting', 'banner', 'ตั้งค่า', 'admin.banner.setting.index', 'banner.setting.manage', 99],

            // module : popup
            ['popup-item', 'popup', 'Popup', 'admin.popup.item.index', 'popup.item.view', 1],
            ['popup-setting', 'popup', 'ตั้งค่า', 'admin.popup.setting.index', 'popup.setting.manage', 99],

            // module : intropage
            ['intropage-item', 'intropage', 'intropage', 'admin.intropage.item.index', 'intropage.item.view', 1],

            // module : page
            ['page-item', 'page', 'หน้าเพจ', 'admin.page.item.index', 'page.item.view', 1],

            // module : contactus
            ['contactus-item', 'contactus', 'ติดต่อเรา', 'admin.contactus.item.index', 'contactus.item.view', 1],

            // จัดการระบบ
            ['system-user', 'system', 'จัดการผู้ใช้งาน', 'admin.system.user.index', 'system.user.view', 1],
            ['system-usergroup', 'system', 'จัดการกลุ่มผู้ใช้งาน', 'admin.system.usergroup.index', 'system.usergroup.view', 2],
            ['system-menu', 'system', 'จัดการเมนู', 'admin.system.menu.index', 'system.menu.view', 4],
            ['system-template', 'system', 'จัดการ Template', 'admin.system.template.index', 'system.template.view', 5],
            ['system-back-log-access', 'system', 'ประวัติการใช้งาน - หลังบ้าน', 'admin.system.backlog.access', 'system.backlog.access', 11],
            ['system-back-log-action', 'system', 'ประวัติการกระทำ - หลังบ้าน', 'admin.system.backlog.action', 'system.backlog.action', 12],
            ['system-back-log-login', 'system', 'ประวัติการเข้าสู่ระบบ - หลังบ้าน', 'admin.system.backlog.login', 'system.backlog.login', 13],
            ['system-front-log-access', 'system', 'ประวัติการใช้งาน - หน้าบ้าน', 'admin.system.frontlog.access', 'system.frontlog.access', 14],
            ['system-front-log-action', 'system', 'ประวัติการกระทำ - หน้าบ้าน', 'admin.system.frontlog.action', 'system.frontlog.action', 15],
            ['system-front-log-login', 'system', 'ประวัติการเข้าสู่ระบบ - หน้าบ้าน', 'admin.system.frontlog.login', 'system.frontlog.login', 16],
            ['system-file', 'system', 'จัดการไฟล์', 'admin.system.file.index', 'system.file.manage', 98],
            ['system-setting', 'system', 'ตั้งค่าระบบ', 'admin.system.setting.index', 'system.setting.manage', 99],
        ];

        foreach ($menus as [$id, $groupId, $name, $routeName, $actionCode, $sortOrder]) {
            SysMenu::updateOrCreate(['id' => $id], [
                'menu_group_id' => $groupId,
                'name' => $name,
                'route_name' => $routeName,
                'sort_order' => $sortOrder,
                'action_code' => $actionCode,
                'status' => 'Y',
            ]);
        }
    }
}
