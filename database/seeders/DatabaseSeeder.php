<?php

namespace Database\Seeders;

use App\Models\SysAction;
use App\Models\SysActionGroup;
use App\Models\SysSetting;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. สร้าง Action Group 1 รายการ (id เป็น string)
        $actionGroup = SysActionGroup::create([
            'id' => 'system-user',
            'name' => 'จัดการผู้ใช้งาน',
            'sort_order' => 1,
        ]);

        // 2. สร้าง Actions 3 รายการ (ใช้ code เป็น id ด้วย)
        $action1 = SysAction::create([
            'id' => 'system.user.view',
            'action_group_id' => $actionGroup->id,
            'parent_id' => null,
            'code' => 'system.user.view',
            'name' => 'ดูรายการผู้ใช้งาน',
            'sort_order' => 1,
        ]);

        $action2 = SysAction::create([
            'id' => 'system.user.create',
            'action_group_id' => $actionGroup->id,
            'parent_id' => null,
            'code' => 'system.user.create',
            'name' => 'เพิ่ม/แก้ไขผู้ใช้งาน',
            'sort_order' => 2,
        ]);

        $action3 = SysAction::create([
            'id' => 'system.user.delete',
            'action_group_id' => $actionGroup->id,
            'parent_id' => null,
            'code' => 'system.user.delete',
            'name' => 'ลบผู้ใช้งาน',
            'sort_order' => 3,
        ]);

        // 3. สร้าง UserGroup 1 รายการ
        $adminGroup = UserGroup::create([
            'name' => 'Super Admin',
            'description' => 'ผู้ดูแลระบบสูงสุด มีสิทธิ์ทุกอย่าง',
            'status' => 'Y',
        ]);

        // 4. ผูกสิทธิ์ 3 รายการเข้ากับ UserGroup (ลงตาราง sys_usergroup_action)
        $adminGroup->actions()->attach([
            $action1->id,
            $action2->id,
            $action3->id,
        ]);

        // 5. สร้าง User สำหรับเข้าสู่ระบบหลังบ้าน
        User::create([
            'titlename' => 'คุณ',
            'firstname' => 'System',
            'lastname' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password123'),
            'user_type' => 'back',
            'status' => 'Y',
            'usergroup_id' => $adminGroup->id,
        ]);

        // 6. ตัวอย่างการตั้งค่าเว็บไซต์ (กลุ่ม site) — เติมเพิ่มเองได้ภายหลัง
        $settings = [
            ['group' => 'site', 'name' => 'site_name', 'value' => 'My CMS'],
            ['group' => 'site', 'name' => 'site_email', 'value' => 'admin@admin.com'],
            ['group' => 'site', 'name' => 'site_description', 'value' => 'Micro-CMS ติดตั้งง่าย ใช้งานง่าย'],
        ];

        foreach ($settings as $setting) {
            SysSetting::create($setting);
        }
    }
}
