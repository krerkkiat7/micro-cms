<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserGroup;
use App\Models\SysAction;
use App\Models\SysActionGroup;
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
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // 1. สร้าง Action Group 1 รายการ
        $actionGroup = SysActionGroup::create([
            'name' => 'จัดการผู้ใช้งาน',
            'sort_order' => 1,
        ]);

        // 2. สร้าง Actions 3 รายการ
        $action1 = SysAction::create([
            'action_group_id' => $actionGroup->id,
            'code' => 'system.user.view',
            'name' => 'ดูรายการผู้ใช้งาน',
        ]);

        $action2 = SysAction::create([
            'action_group_id' => $actionGroup->id,
            'code' => 'system.user.create',
            'name' => 'เพิ่ม/แก้ไขผู้ใช้งาน',
        ]);

        $action3 = SysAction::create([
            'action_group_id' => $actionGroup->id,
            'code' => 'system.user.delete',
            'name' => 'ลบผู้ใช้งาน',
        ]);

        // 3. สร้าง UserGroup 1 รายการ
        $adminGroup = UserGroup::create([
            'name' => 'Super Admin',
            'description' => 'ผู้ดูแลระบบสูงสุด มีสิทธิ์ทุกอย่าง',
        ]);

        // 4. ผูกสิทธิ์ 3 รายการเข้ากับ UserGroup (ลงตาราง sys_usergroup_action)
        // ใช้ sync() หรือ attach() ผ่าน Relationship ที่ตั้งไว้ใน UserGroup Model
        $adminGroup->actions()->attach([
            $action1->id,
            $action2->id,
            $action3->id,
        ]);

        // 5. สร้าง User สำหรับเข้าสู่ระบบหลังบ้าน
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password123'), 
            'user_type' => 'back',
            'usergroup_id' => $adminGroup->id,
        ]);
    }
}
