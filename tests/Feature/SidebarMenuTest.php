<?php

use App\Models\SysAction;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('super admin sees every menu group and item', function () {
    $admin = User::where('email', 'admin@admin.com')->firstOrFail();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->has('menu', 7)
            ->where('menu.0.id', 'article')       // เรียงตาม sort_order
            ->where('menu.0.icon', 'Newspaper')
            ->where('menu.6.id', 'system')
            ->where('menu.6.icon', 'Settings')
            // 'จัดการไฟล์' ไม่ผ่าน sys_menu แล้ว — เปลี่ยนเป็นลิงก์ hardcode ใน AppSidebar.vue (ต่อจากโปรไฟล์)
            ->has('menu.6.items', 11)
            ->where('menu.6.items.0.icon', 'Users')
            // route ลงท้าย .index → activePattern ครอบทุกหน้าในโมดูล
            ->where('menu.6.items.0.activePattern', 'admin.system.user.*')
            ->where('menu.6.items.4.activePattern', 'admin.system.backlog.access.*')
        );
});

test('menu items are filtered by permission and empty groups drop out', function () {
    $group = UserGroup::create(['name' => 'Limited', 'status' => 'Y']);
    $group->actions()->attach(
        SysAction::whereIn('code', ['system.user.view', 'system.menu.view'])->pluck('id')
    );
    $user = User::factory()->create(['usergroup_id' => $group->id]);

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->has('menu', 1)                       // เหลือแค่กลุ่ม system
            ->where('menu.0.id', 'system')
            ->has('menu.0.items', 2)
            ->where('menu.0.items.0.id', 'system-user')
            ->where('menu.0.items.0.href', route('admin.system.user.index')) // route มีจริงแล้ว
            ->where('menu.0.items.0.activePattern', 'admin.system.user.*')
            ->where('menu.0.items.1.id', 'system-menu')
            ->where('menu.0.items.1.href', route('admin.system.menu.index')) // route มีจริงแล้ว
            ->where('menu.0.items.1.activePattern', 'admin.system.menu.*')
        );
});

test('user without a usergroup sees no db menu', function () {
    $user = User::factory()->create(['usergroup_id' => null]);

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('menu', []));
});
