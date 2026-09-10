<?php

use App\Models\LogBackAction;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function backLogActionGroupId(): int
{
    return UserGroup::where('name', 'Super Admin')->value('id');
}

// ---------------------------------------------------------------- การบันทึก: system.user

test('เพิ่มผู้ใช้งาน บันทึก action create', function () {
    $me = actingAsUserWithPermissions(['system.user.manage']);

    $this->post(route('admin.system.user.store'), [
        'titlename' => 'คุณ',
        'firstname' => 'New',
        'lastname' => 'Person',
        'email' => 'newperson@example.com',
        'usergroup_id' => backLogActionGroupId(),
        'password' => 'Aa1!aaaa',
        'password_confirmation' => 'Aa1!aaaa',
        'status' => 'Y',
    ])->assertSessionHasNoErrors();

    $created = User::where('email', 'newperson@example.com')->firstOrFail();
    $log = LogBackAction::sole();

    expect($log->module_code)->toBe('system.user')
        ->and($log->action_type)->toBe('create')
        ->and($log->user_id)->toBe($me->id)
        ->and($log->value_string)->toBe($created->name)
        ->and($log->ref_id)->toBe($created->id)
        ->and($log->remote_ip)->not->toBeNull();
});

test('เข้าหน้าแก้ไข / บันทึกแก้ไข / ลบ บันทึก action view / update / delete', function () {
    actingAsUserWithPermissions(['system.user.view', 'system.user.manage', 'system.user.delete']);
    $target = User::factory()->create(['firstname' => 'Target', 'lastname' => 'User', 'user_type' => 'back']);

    $this->get(route('admin.system.user.edit', $target->id))->assertOk();
    $this->put(route('admin.system.user.update', $target->id), [
        'titlename' => 'คุณ',
        'firstname' => 'Target',
        'lastname' => 'User',
        'email' => $target->email,
        'usergroup_id' => backLogActionGroupId(),
        'status' => 'Y',
    ])->assertSessionHasNoErrors();
    $this->delete(route('admin.system.user.destroy', $target->id))->assertSessionHasNoErrors();

    expect(LogBackAction::orderBy('id')->pluck('action_type')->all())
        ->toBe(['view', 'update', 'delete']);
    expect(LogBackAction::where('action_type', 'delete')->sole())
        ->module_code->toBe('system.user')
        ->value_string->toBe('คุณ Target User')
        ->ref_id->toBe($target->id);
});

test('หน้าเปลี่ยนรหัสผ่าน บันทึก action ใต้ module_code system.user.password', function () {
    actingAsUserWithPermissions(['system.user.password']);
    $target = User::factory()->create(['user_type' => 'back']);

    $this->get(route('admin.system.user.password', $target->id))->assertOk();
    $this->put(route('admin.system.user.password.update', $target->id), [
        'password' => 'Aa1!aaaa',
        'password_confirmation' => 'Aa1!aaaa',
    ])->assertSessionHasNoErrors();

    expect(LogBackAction::where('module_code', 'system.user.password')->pluck('action_type')->all())
        ->toBe(['view', 'update']);
});

test('เข้าหน้ารายการผู้ใช้งาน ไม่บันทึก action', function () {
    actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.index'))->assertOk();

    expect(LogBackAction::count())->toBe(0);
});

// ---------------------------------------------------------------- การบันทึก: system.usergroup

test('จัดการกลุ่มผู้ใช้งาน บันทึก action ตาม module_code system.usergroup(.rights)', function () {
    actingAsUserWithPermissions([
        'system.usergroup.view', 'system.usergroup.manage',
        'system.usergroup.delete', 'system.usergroup.rights',
    ]);

    $this->post(route('admin.system.usergroup.store'), [
        'name' => 'กลุ่มทดสอบ', 'description' => '-', 'status' => 'Y',
    ])->assertSessionHasNoErrors();
    $group = UserGroup::where('name', 'กลุ่มทดสอบ')->firstOrFail();

    $this->get(route('admin.system.usergroup.edit', $group->id))->assertOk();
    $this->put(route('admin.system.usergroup.update', $group->id), [
        'name' => 'กลุ่มทดสอบ', 'description' => 'แก้แล้ว', 'status' => 'Y',
    ])->assertSessionHasNoErrors();
    $this->get(route('admin.system.usergroup.rights', $group->id))->assertOk();
    $this->put(route('admin.system.usergroup.rights.update', $group->id), ['action_ids' => []])
        ->assertSessionHasNoErrors();
    $this->delete(route('admin.system.usergroup.destroy', $group->id))->assertSessionHasNoErrors();

    $rows = LogBackAction::orderBy('id')->get(['module_code', 'action_type']);
    expect($rows->map(fn ($r) => "{$r->module_code}:{$r->action_type}")->all())->toBe([
        'system.usergroup:create',
        'system.usergroup:view',
        'system.usergroup:update',
        'system.usergroup.rights:view',
        'system.usergroup.rights:update',
        'system.usergroup:delete',
    ]);
});

// ---------------------------------------------------------------- หน้ารายการ (viewer)

test('หน้ารายการ redirect ไป dashboard เมื่อไม่มีสิทธิ์ system.backlog.action', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.backlog.action.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('หน้ารายการแสดงผล + dropdown โมดูล/ประเภท จาก distinct', function () {
    $me = actingAsUserWithPermissions(['system.backlog.action']);
    LogBackAction::record('system.user', 'create', 'Somchai Jaidee', 10);
    LogBackAction::record('system.usergroup', 'update', 'บรรณาธิการ', 3);

    $this->get(route('admin.system.backlog.action.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/BackLogAction/Index')
            ->where('sort', 'created_at')
            ->where('direction', 'desc')
            ->where('moduleOptions', ['system.user', 'system.usergroup'])
            ->where('actionTypeOptions', ['create', 'update'])
            ->where('logs.data.0.name', $me->name));
});

test('กรองตามโมดูลและประเภทการกระทำ', function () {
    actingAsUserWithPermissions(['system.backlog.action']);
    LogBackAction::record('system.user', 'create', 'A', 1);
    LogBackAction::record('system.user', 'delete', 'B', 2);
    LogBackAction::record('system.usergroup', 'create', 'C', 3);

    $this->get(route('admin.system.backlog.action.index', ['module_code' => 'system.usergroup']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.value_string', 'C'));

    $this->get(route('admin.system.backlog.action.index', ['action_type' => 'delete']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.value_string', 'B'));

    $this->get(route('admin.system.backlog.action.index', ['q' => 'C']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)
            ->where('logs.data.0.value_string', 'C'));
});

test('แถวที่ไม่มี user แสดง name เป็น null', function () {
    actingAsUserWithPermissions(['system.backlog.action']);
    LogBackAction::create(['module_code' => 'x', 'action_type' => 'view', 'user_id' => null]);

    $this->get(route('admin.system.backlog.action.index', ['q' => '', 'module_code' => 'x']))
        ->assertInertia(fn (Assert $page) => $page->where('logs.data.0.name', null));
});

test('เมนู sidebar ของ backlog action คลิกได้แล้ว', function () {
    actingAsUserWithPermissions(['system.backlog.action']);

    $this->get(route('admin.system.backlog.action.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('menu.0.items.0.href', route('admin.system.backlog.action.index'))
            ->where('menu.0.items.0.activePattern', 'admin.system.backlog.action.*'));
});
