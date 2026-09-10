<?php

use App\Models\LogBackLogin;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

// ---------------------------------------------------------------- การบันทึก

test('login สำเร็จ บันทึก log_back_login', function () {
    $user = User::factory()->create();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $log = LogBackLogin::sole();
    expect($log->log_type)->toBe('login')
        ->and($log->result)->toBe('success')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->username)->toBe($user->email)
        ->and($log->note)->toBe('เข้าสู่ระบบสำเร็จ')
        ->and($log->remote_ip)->not->toBeNull();
});

test('login รหัสผ่านผิด บันทึกเป็น fail', function () {
    $user = User::factory()->create();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong-password']);

    $log = LogBackLogin::sole();
    expect($log->result)->toBe('fail')
        ->and($log->user_id)->toBeNull()
        ->and($log->note)->toBe('รหัสผ่านไม่ถูกต้อง');
});

test('login อีเมลที่ไม่มีในระบบ บันทึกเป็น fail', function () {
    $this->post('/admin/login', ['email' => 'nobody@example.com', 'password' => 'whatever']);

    expect(LogBackLogin::sole()->note)->toBe('ไม่พบบัญชีผู้ใช้งานหลังบ้าน');
});

test('login บัญชีถูกระงับ บันทึกเป็น block', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password']);

    $log = LogBackLogin::sole();
    expect($log->result)->toBe('block')
        ->and($log->user_id)->toBeNull()
        ->and($log->note)->toContain('ระงับ');
});

test('login ผิดหลายครั้งจน throttle บันทึกเป็น block', function () {
    $user = User::factory()->create();

    foreach (range(1, 6) as $i) {
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    expect(LogBackLogin::where('result', 'block')->where('note', 'like', '%ถูกระงับชั่วคราว%')->exists())
        ->toBeTrue();
});

test('logout บันทึก log_back_login', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/admin/logout')->assertRedirect('/admin/login');

    $log = LogBackLogin::sole();
    expect($log->log_type)->toBe('logout')
        ->and($log->result)->toBe('success')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->username)->toBe($user->email);
});

// ---------------------------------------------------------------- หน้ารายการ (viewer)

test('หน้ารายการ redirect ไป dashboard เมื่อไม่มีสิทธิ์ system.backlog.login', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.backlog.login.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('หน้ารายการแสดงผลได้เมื่อมีสิทธิ์', function () {
    $user = actingAsUserWithPermissions(['system.backlog.login']);
    LogBackLogin::loginSuccess($user);
    LogBackLogin::loginFailed('hacker@example.com', 'รหัสผ่านไม่ถูกต้อง');

    $this->get(route('admin.system.backlog.login.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/BackLogLogin/Index')
            ->where('sort', 'created_at')
            ->where('direction', 'desc')
            ->has('logs.data', 2));
});

test('กรองตามประเภทและผลลัพธ์', function () {
    $user = actingAsUserWithPermissions(['system.backlog.login']);
    LogBackLogin::loginSuccess($user);
    LogBackLogin::loginFailed('x@example.com', 'รหัสผ่านไม่ถูกต้อง');
    LogBackLogin::logout($user->id, $user->email);

    $this->get(route('admin.system.backlog.login.index', ['result' => 'fail']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.result', 'fail'));

    $this->get(route('admin.system.backlog.login.index', ['log_type' => 'logout']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.log_type', 'logout'));
});

test('ค้นหาจาก username / IP / note', function () {
    $user = actingAsUserWithPermissions(['system.backlog.login']);
    LogBackLogin::loginFailed('target@example.com', 'ไม่พบบัญชีผู้ใช้งานหลังบ้าน');
    LogBackLogin::loginSuccess($user);

    $this->get(route('admin.system.backlog.login.index', ['q' => 'target@example.com']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.username', 'target@example.com')
            ->where('logs.data.0.name', null));
});

test('เมนู sidebar ของ backlog login คลิกได้แล้ว', function () {
    actingAsUserWithPermissions(['system.backlog.login']);

    $this->get(route('admin.system.backlog.login.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu.0.items.0.href', route('admin.system.backlog.login.index'))
            ->where('menu.0.items.0.activePattern', 'admin.system.backlog.login.*'));
});
