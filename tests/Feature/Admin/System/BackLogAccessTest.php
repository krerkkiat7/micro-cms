<?php

use App\Models\LogBackAccess;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

// ---------------------------------------------------------------- การบันทึก

test('เข้าหน้ารายการ บันทึก log 1 แถว', function () {
    $user = actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.index'))->assertOk();

    $log = LogBackAccess::sole();
    expect($log->user_id)->toBe($user->id)
        ->and($log->title_name)->toBe('จัดการผู้ใช้งาน')
        ->and($log->uri_string)->toBe('/admin/system/user')
        ->and(strlen($log->token))->toBe(26)
        ->and($log->last_visited->toDateTimeString())->toBe($log->created_at->toDateTimeString());
});

test('เข้าหน้าเพิ่ม/แก้ไข บันทึกด้วยชื่อหน้าที่ต่างกัน', function () {
    actingAsUserWithPermissions(['system.user.view', 'system.user.manage']);
    $target = User::factory()->create(['user_type' => 'back']);

    $this->get(route('admin.system.user.add'))->assertOk();
    $this->get(route('admin.system.user.edit', $target->id))->assertOk();

    expect(LogBackAccess::pluck('title_name')->all())
        ->toBe(['เพิ่มผู้ใช้งาน', 'แก้ไขผู้ใช้งาน']);
});

test('ค้นหา/กรอง/แบ่งหน้า ไม่บันทึก log', function () {
    actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.index', ['q' => 'abc']))->assertOk();
    $this->get(route('admin.system.user.index', ['page' => 2]))->assertOk();
    $this->get(route('admin.system.user.index', ['status' => 'Y']))->assertOk();

    expect(LogBackAccess::count())->toBe(0);
});

test('หน้าที่ถูกปฏิเสธสิทธิ์ ไม่บันทึก log', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.user.index'))->assertRedirect(route('admin.dashboard'));

    // dashboard ที่ถูก redirect ไปยังบันทึกของตัวเอง แต่ต้องไม่มีของหน้า user
    expect(LogBackAccess::where('title_name', 'จัดการผู้ใช้งาน')->exists())->toBeFalse();
});

// ---------------------------------------------------------------- shared prop

test('accessLog.token ถูกแชร์เป็น prop บนหน้าที่บันทึก log', function () {
    actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('accessLog.token', fn ($token) => is_string($token) && strlen($token) === 26));
});

test('accessLog.token เป็น null บน request ค้นหา', function () {
    actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.index', ['q' => 'abc']))
        ->assertInertia(fn (Assert $page) => $page->where('accessLog.token', null));
});

// ---------------------------------------------------------------- ping (keep-alive)

test('ping อัปเดต last_visited ของแถวตัวเอง', function () {
    $user = actingAsUserWithPermissions([]);
    $log = LogBackAccess::create(['user_id' => $user->id, 'uri_string' => '/x', 'title_name' => 'x']);
    $before = $log->last_visited->timestamp;

    $this->travel(5)->minutes();

    $this->post(route('admin.system.backlog.access.ping'), ['token' => $log->token])
        ->assertNoContent();

    expect($log->fresh()->last_visited->timestamp)->toBeGreaterThan($before);
});

test('ping ไม่แตะแถวของผู้ใช้อื่น', function () {
    actingAsUserWithPermissions([]);
    $other = User::factory()->create();
    $log = LogBackAccess::create(['user_id' => $other->id, 'uri_string' => '/x', 'title_name' => 'x']);
    $before = $log->last_visited->timestamp;

    $this->travel(5)->minutes();

    $this->post(route('admin.system.backlog.access.ping'), ['token' => $log->token])
        ->assertNoContent();

    expect($log->fresh()->last_visited->timestamp)->toBe($before);
});

test('ping ที่ token ไม่ถูกต้อง ตอบ 204 เฉย ๆ', function () {
    actingAsUserWithPermissions([]);

    $this->post(route('admin.system.backlog.access.ping'), ['token' => 'too-short'])
        ->assertNoContent();
    $this->post(route('admin.system.backlog.access.ping'))->assertNoContent();
});

test('ping ต้องล็อกอินก่อน', function () {
    $this->post(route('admin.system.backlog.access.ping'), ['token' => str_repeat('x', 26)])
        ->assertRedirect(route('admin.login'));
});

// ---------------------------------------------------------------- หน้ารายการ (viewer)

test('หน้ารายการ redirect ไป dashboard เมื่อไม่มีสิทธิ์ system.backlog.access', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.backlog.access.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('หน้ารายการแสดงผลได้เมื่อมีสิทธิ์', function () {
    $user = actingAsUserWithPermissions(['system.backlog.access']);
    LogBackAccess::create([
        'user_id' => $user->id,
        'uri_string' => '/admin/dashboard',
        'title_name' => 'แดชบอร์ด',
        'remote_ip' => '203.0.113.5',
    ]);

    // ใส่ q เพื่อไม่ให้หน้ารายการบันทึก log ของตัวเองมาปน
    $this->get(route('admin.system.backlog.access.index', ['q' => '203.0.113.5']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/BackLogAccess/Index')
            ->where('sort', 'created_at')
            ->where('direction', 'desc')
            ->has('logs.data', 1)
            ->where('logs.data.0.name', $user->name)
            ->where('logs.data.0.remote_ip', '203.0.113.5')
            ->where('logs.data.0.title_name', 'แดชบอร์ด'));
});

test('ค้นหากรองตาม URL / ชื่อหน้า / IP', function () {
    $user = actingAsUserWithPermissions(['system.backlog.access']);
    LogBackAccess::create(['user_id' => $user->id, 'uri_string' => '/admin/system/user', 'title_name' => 'จัดการผู้ใช้งาน', 'remote_ip' => '10.0.0.1']);
    LogBackAccess::create(['user_id' => $user->id, 'uri_string' => '/admin/profile', 'title_name' => 'ข้อมูลส่วนตัว', 'remote_ip' => '10.0.0.2']);

    $this->get(route('admin.system.backlog.access.index', ['q' => 'profile']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.uri_string', '/admin/profile'));

    $this->get(route('admin.system.backlog.access.index', ['q' => '10.0.0.1']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.remote_ip', '10.0.0.1'));
});

test('กรองตามช่วงวันที่ที่เข้าชม', function () {
    $user = actingAsUserWithPermissions(['system.backlog.access']);
    $old = LogBackAccess::create(['user_id' => $user->id, 'uri_string' => '/old', 'title_name' => 'old']);
    $old->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
    LogBackAccess::create(['user_id' => $user->id, 'uri_string' => '/new', 'title_name' => 'new']);

    $this->get(route('admin.system.backlog.access.index', ['date_from' => now()->subDays(2)->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.uri_string', '/new'));
});

test('แถวที่ไม่มี user แสดง name เป็น null', function () {
    actingAsUserWithPermissions(['system.backlog.access']);
    LogBackAccess::create(['user_id' => null, 'uri_string' => '/x', 'title_name' => 'x']);

    $this->get(route('admin.system.backlog.access.index', ['q' => '/x']))
        ->assertInertia(fn (Assert $page) => $page->where('logs.data.0.name', null));
});

test('เมนู sidebar ของ backlog access คลิกได้แล้ว (มี route จริง)', function () {
    actingAsUserWithPermissions(['system.backlog.access']);

    $this->get(route('admin.system.backlog.access.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu.0.items.0.href', route('admin.system.backlog.access.index'))
            ->where('menu.0.items.0.activePattern', 'admin.system.backlog.access.*'));
});
