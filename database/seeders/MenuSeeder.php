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
            ['id' => 'content', 'name' => 'โมดูลเนื้อหา', 'sort_order' => 1],
            ['id' => 'system', 'name' => 'จัดการระบบ', 'sort_order' => 2],
        ];

        foreach ($groups as $group) {
            SysMenuGroup::updateOrCreate(['id' => $group['id']], $group + ['status' => 'Y']);
        }

        // 2. เมนูย่อย — [id, group, name, route_name, action_code]
        $menus = [
            // โมดูลเนื้อหา
            ['content-article', 'content', 'บทความ', 'admin.article.index', 'article.view'],
            ['content-banner', 'content', 'Banner', 'admin.banner.index', 'banner.view'],
            ['content-popup', 'content', 'Popup', 'admin.popup.index', 'popup.view'],
            ['content-intropage', 'content', 'Intro Page', 'admin.intropage.index', 'intropage.view'],
            ['content-page', 'content', 'หน้าเดี่ยว', 'admin.page.index', 'page.view'],
            ['content-contact', 'content', 'ติดต่อเรา', 'admin.contact.index', 'contact.view'],

            // จัดการระบบ
            ['system-dashboard', 'system', 'Dashboard', 'admin.dashboard', null],
            ['system-user', 'system', 'จัดการผู้ใช้งาน', 'admin.system.users.index', 'system.user.view'],
            ['system-usergroup', 'system', 'จัดการกลุ่มผู้ใช้งาน', 'admin.system.usergroups.index', 'system.usergroup.view'],
            ['system-menu', 'system', 'จัดการเมนู', 'admin.system.menus.index', 'system.menu.view'],
            ['system-template', 'system', 'จัดการ Template', 'admin.system.templates.index', 'system.template.view'],
            ['system-log', 'system', 'ประวัติการใช้งาน', 'admin.system.logs.login', 'system.log.login'],
            ['system-setting', 'system', 'ตั้งค่าระบบ', 'admin.system.settings.edit', 'system.setting.view'],
            ['system-file', 'system', 'จัดการไฟล์', 'admin.system.files.index', 'system.file.view'],
            ['system-profile', 'system', 'โปรไฟล์', 'admin.profile.edit', null],
        ];

        $sortByGroup = [];

        foreach ($menus as [$id, $groupId, $name, $routeName, $actionCode]) {
            $sortByGroup[$groupId] = ($sortByGroup[$groupId] ?? 0) + 10;

            SysMenu::updateOrCreate(['id' => $id], [
                'menu_group_id' => $groupId,
                'name' => $name,
                'route_name' => $routeName,
                'sort_order' => $sortByGroup[$groupId],
                'action_code' => $actionCode,
                'status' => 'Y',
            ]);
        }
    }
}
