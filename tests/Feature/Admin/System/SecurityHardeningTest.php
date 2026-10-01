<?php

use App\Models\SysSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\Report\ViewReport;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function systemGroup(): UserGroup
{
    return UserGroup::where('name', 'Super Admin')->firstOrFail();
}

function superAdminUser(): User
{
    return User::where('email', 'admin@mycms.com')->firstOrFail();
}

// ---------------------------------------------------------------- สถานะผู้ใช้ทุก request

test('a user suspended while logged in is logged out on the next request', function () {
    $user = actingAsUserWithPermissions([]);

    $this->get(route('admin.dashboard'))->assertOk();

    $user->forceFill(['status' => 'N'])->save();

    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a disabled usergroup grants no permissions', function () {
    $user = actingAsUserWithPermissions(['system.user.view']);

    expect($user->hasPermission('system.user.view'))->toBeTrue();

    $user->group->update(['status' => 'N']);
    $user->refresh();

    expect($user->hasPermission('system.user.view'))->toBeFalse()
        ->and($user->getPermissionsArray())->toBe([]);

    $this->get(route('admin.system.user.index'))->assertRedirect(route('admin.dashboard'));
});

// ---------------------------------------------------------------- กันยกระดับสิทธิ์

test('a non-system user cannot create a user in a system group', function () {
    actingAsUserWithPermissions(['system.user.manage']);

    $this->post(route('admin.system.user.store'), [
        'titlename' => 'คุณ',
        'firstname' => 'Sneaky',
        'lastname' => 'Admin',
        'email' => 'sneaky@example.com',
        'usergroup_id' => systemGroup()->id,
        'password' => 'Aa1!aaaa',
        'password_confirmation' => 'Aa1!aaaa',
        'status' => 'Y',
    ])->assertSessionHasErrors('usergroup_id');

    $this->assertDatabaseMissing('sys_user', ['email' => 'sneaky@example.com']);
});

test('a non-system user cannot move themselves into a system group', function () {
    $me = actingAsUserWithPermissions(['system.user.manage']);

    $this->put(route('admin.system.user.update', $me->id), [
        'titlename' => 'คุณ',
        'firstname' => $me->firstname,
        'lastname' => $me->lastname,
        'email' => $me->email,
        'usergroup_id' => systemGroup()->id,
        'status' => 'Y',
    ])->assertSessionHasErrors('usergroup_id');

    expect($me->fresh()->usergroup_id)->not->toBe(systemGroup()->id);
});

test('a non-system user cannot edit, delete or reset the password of a system-group user', function () {
    actingAsUserWithPermissions(['system.user.view', 'system.user.manage', 'system.user.delete', 'system.user.password']);
    $admin = superAdminUser();

    $this->get(route('admin.system.user.edit', $admin->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('protected', true)
            ->where('can.manage', false)
            ->where('can.delete', false)
            ->where('can.password', false)
            ->whereType('systemGroupMessage', 'string'));

    $this->put(route('admin.system.user.update', $admin->id), [
        'titlename' => 'คุณ',
        'firstname' => 'Hacked',
        'lastname' => 'Admin',
        'email' => $admin->email,
        'usergroup_id' => systemGroup()->id,
        'status' => 'Y',
    ])->assertSessionHasErrors('usergroup_id');

    $this->delete(route('admin.system.user.destroy', $admin->id))->assertSessionHasErrors('user');

    $this->put(route('admin.system.user.password.update', $admin->id), [
        'password' => 'NewPass1!',
        'password_confirmation' => 'NewPass1!',
    ])->assertRedirect(route('admin.system.user.edit', $admin->id));

    $admin->refresh();
    expect($admin->firstname)->not->toBe('Hacked')
        ->and($admin->trashed())->toBeFalse()
        ->and(Hash::check('NewPass1!', $admin->password))->toBeFalse();
});

test('system groups are disabled in the group dropdown for non-system users', function () {
    actingAsUserWithPermissions(['system.user.manage']);

    $this->get(route('admin.system.user.add'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('userGroups', fn ($groups) => collect($groups)->firstWhere('id', systemGroup()->id)['disabled'] === true)
            ->whereType('systemGroupMessage', 'string'));
});

test('a system-group user can move a user into a system group', function () {
    $this->actingAs(superAdminUser());
    $target = User::factory()->create(['user_type' => 'back']);

    $this->put(route('admin.system.user.update', $target->id), [
        'titlename' => 'คุณ',
        'firstname' => $target->firstname,
        'lastname' => $target->lastname,
        'email' => $target->email,
        'usergroup_id' => systemGroup()->id,
        'status' => 'Y',
    ])->assertSessionHasNoErrors();

    expect($target->fresh()->usergroup_id)->toBe(systemGroup()->id);
});

test('a user cannot change the rights of their own group', function () {
    $me = actingAsUserWithPermissions(['system.usergroup.rights']);

    $this->get(route('admin.system.usergroup.rights', $me->usergroup_id))
        ->assertInertia(fn (Assert $page) => $page->where('isOwnGroup', true));

    $this->put(route('admin.system.usergroup.rights.update', $me->usergroup_id), [
        'action_ids' => ['system001'],
    ])->assertSessionHasErrors('action_ids');

    expect($me->group->actions()->pluck('code')->all())->toBe(['system.usergroup.rights']);
});

test('saving a user in a now-disabled group keeps working', function () {
    $this->actingAs(superAdminUser());
    $group = UserGroup::create(['name' => 'Old team', 'status' => 'Y']);
    $target = User::factory()->create(['user_type' => 'back', 'usergroup_id' => $group->id]);
    $group->update(['status' => 'N']);

    $this->put(route('admin.system.user.update', $target->id), [
        'titlename' => 'คุณ',
        'firstname' => 'Still',
        'lastname' => 'Here',
        'email' => $target->email,
        'usergroup_id' => $group->id,
        'status' => 'Y',
    ])->assertSessionHasNoErrors();

    expect($target->fresh()->firstname)->toBe('Still');
});

test('re-enabling a locked user resets the failed login counter', function () {
    $this->actingAs(superAdminUser());
    $target = User::factory()->create([
        'user_type' => 'back', 'status' => 'N', 'failed_login_count' => 5, 'last_failed_login_at' => now(),
        'usergroup_id' => UserGroup::create(['name' => 'Team', 'status' => 'Y'])->id,
    ]);

    $this->put(route('admin.system.user.update', $target->id), [
        'titlename' => 'คุณ',
        'firstname' => $target->firstname,
        'lastname' => $target->lastname,
        'email' => $target->email,
        'usergroup_id' => $target->usergroup_id,
        'status' => 'Y',
    ])->assertSessionHasNoErrors();

    $target->refresh();
    expect($target->status)->toBe('Y')
        ->and($target->failed_login_count)->toBe(0)
        ->and($target->last_failed_login_at)->toBeNull();
});

// ---------------------------------------------------------------- ค่าลับในตั้งค่าระบบ

test('setting secrets are never sent to the page and a blank value keeps the stored one', function () {
    $this->actingAs(superAdminUser());

    foreach ([['smtp', 'password', 'smtp-secret'], ['turnstile', 'key_secret', 'ts-secret'], ['turnstile', 'site_key', 'ts-site']] as [$group, $name, $value]) {
        SysSetting::create(['group' => $group, 'name' => $name, 'value' => $value]);
    }
    Setting::forgetAll();

    $response = $this->get(route('admin.system.setting.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('settings.smtp.password')
            ->missing('settings.turnstile.key_secret')
            ->where('secretsSet.smtp', true)
            ->where('secretsSet.turnstile', true));

    expect($response->getContent())->not->toContain('smtp-secret')->not->toContain('ts-secret');

    $this->put(route('admin.system.setting.update.smtp'), [
        'host' => 'smtp.example.com', 'port' => 587, 'use_auth' => 'Y', 'username' => 'mailer',
        'password' => '', 'ssl_type' => 'tls',
    ])->assertSessionHasNoErrors();

    $this->put(route('admin.system.setting.update.turnstile'), ['site_key' => 'ts-site', 'key_secret' => ''])
        ->assertSessionHasNoErrors();

    Setting::forgetAll();
    expect(Setting::get('smtp', 'password'))->toBe('smtp-secret')
        ->and(Setting::get('turnstile', 'key_secret'))->toBe('ts-secret');
});

// ---------------------------------------------------------------- อื่น ๆ

test('csv cells that look like formulas are neutralised', function () {
    expect(ViewReport::csvCell('=HYPERLINK("x")'))->toBe('\'=HYPERLINK("x")')
        ->and(ViewReport::csvCell('+1'))->toBe('+1') // ตัวเลขจริงไม่แตะ
        ->and(ViewReport::csvCell('-5'))->toBe('-5')
        ->and(ViewReport::csvCell('@cmd'))->toBe("'@cmd")
        ->and(ViewReport::csvCell('-x'))->toBe("'-x")
        ->and(ViewReport::csvCell('ปกติ'))->toBe('ปกติ')
        ->and(ViewReport::csvCell(12))->toBe(12);
});

test('admin, front and file responses carry the basic security headers', function () {
    $this->actingAs(superAdminUser())
        ->get(route('admin.dashboard'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get('/robots.txt')->assertHeader('X-Content-Type-Options', 'nosniff');
});
