<?php

use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validUserPayload(int $usergroupId, array $overrides = []): array
{
    return array_merge([
        'titlename' => 'คุณ',
        'firstname' => 'New',
        'lastname' => 'Person',
        'email' => 'newperson@example.com',
        'mobile' => '0800000000',
        'phone' => '',
        'line' => '',
        'facebook' => '',
        'usergroup_id' => $usergroupId,
        'password' => 'Aa1!aaaa',
        'password_confirmation' => 'Aa1!aaaa',
        'status' => 'Y',
    ], $overrides);
}

function superAdminGroupId(): int
{
    return UserGroup::where('name', 'Super Admin')->value('id');
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without system.user.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.user.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders for a user with system.user.view', function () {
    actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/User/Index')
            ->has('users.data')
            ->has('userGroups')
        );
});

test('index only lists back users and matches the search term', function () {
    actingAsUserWithPermissions(['system.user.view']);

    User::factory()->create(['firstname' => 'Somchai', 'lastname' => 'Jaidee', 'email' => 'somchai@example.com']);
    User::factory()->create(['firstname' => 'Somsri', 'lastname' => 'Rakdee', 'email' => 'somsri@example.com']);
    User::factory()->front()->create(['firstname' => 'Fronty', 'lastname' => 'User', 'email' => 'front@example.com']);

    $this->get(route('admin.system.user.index', ['q' => 'somchai']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'somchai@example.com')
        );

    $this->get(route('admin.system.user.index', ['q' => 'Fronty']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
});

test('search still filters when the term is the string "0"', function () {
    actingAsUserWithPermissions(['system.user.view']);

    User::factory()->create(['firstname' => 'Zero0', 'lastname' => 'User', 'email' => 'zero-0@example.com']);
    User::factory()->create(['firstname' => 'Plain', 'lastname' => 'Name', 'email' => 'plain@example.com']);

    // "0" ต้องกรองจริง — คนที่ไม่มีเลข 0 ต้องไม่ติดมาด้วย
    $this->get(route('admin.system.user.index', ['q' => '0']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($rows) => collect($rows)->pluck('email')->doesntContain('plain@example.com')));
});

test('index filters by status', function () {
    actingAsUserWithPermissions(['system.user.view']);

    User::factory()->count(2)->create(['status' => 'Y']);
    User::factory()->inactive()->create();

    $this->get(route('admin.system.user.index', ['status' => 'N']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
});

test('index defaults to newest first and accepts a sort override', function () {
    actingAsUserWithPermissions(['system.user.view']);

    User::factory()->create(['firstname' => 'Aaa', 'lastname' => 'Zebrastripe', 'created_at' => now()->subDays(3)]);
    User::factory()->create(['firstname' => 'Zzz', 'lastname' => 'Zebrastripe', 'created_at' => now()->subDay()]);

    // ค่าเริ่มต้น: created_at มากไปน้อย → Zzz (สร้างทีหลัง) มาก่อน
    $this->get(route('admin.system.user.index', ['q' => 'Zebrastripe']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'created_at')
            ->where('direction', 'desc')
            ->where('users.data.0.name', fn ($name) => str_contains($name, 'Zzz'))
        );

    // เรียงตามชื่อ น้อยไปมาก → Aaa มาก่อน
    $this->get(route('admin.system.user.index', ['q' => 'Zebrastripe', 'sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'name')
            ->where('direction', 'asc')
            ->where('users.data.0.name', fn ($name) => str_contains($name, 'Aaa'))
        );
});

// ---------------------------------------------------------------- add / store

test('add page redirects without system.user.manage', function () {
    actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.add'))
        ->assertRedirect(route('admin.system.user.index'));
});

test('add page renders with system.user.manage', function () {
    actingAsUserWithPermissions(['system.user.manage']);

    $this->get(route('admin.system.user.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/User/Add')
            ->has('userGroups')
        );
});

test('store validates required fields', function () {
    actingAsUserWithPermissions(['system.user.manage']);

    $this->from(route('admin.system.user.add'))
        ->post(route('admin.system.user.store'), [])
        ->assertInvalid(['titlename', 'firstname', 'lastname', 'email', 'usergroup_id', 'password', 'status']);
});

test('store rejects a weak password', function () {
    actingAsUserWithPermissions(['system.user.manage']);

    $this->from(route('admin.system.user.add'))
        ->post(route('admin.system.user.store'), validUserPayload(superAdminGroupId(), [
            'password' => 'weakpassword',
            'password_confirmation' => 'weakpassword',
        ]))
        ->assertInvalid(['password']);
});

test('store creates a back user and redirects to edit with a success flash', function () {
    actingAsUserWithPermissions(['system.user.manage']);

    $response = $this->post(route('admin.system.user.store'), validUserPayload(superAdminGroupId()));

    $user = User::where('email', 'newperson@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->user_type)->toBe('back')
        ->and($user->status)->toBe('Y');

    $response->assertRedirect(route('admin.system.user.edit', $user->id))
        ->assertSessionHas('success');
});

test('store scopes email uniqueness to non-deleted back users', function () {
    actingAsUserWithPermissions(['system.user.manage']);
    $groupId = superAdminGroupId();

    User::factory()->create(['email' => 'dupe@example.com']);

    $this->from(route('admin.system.user.add'))
        ->post(route('admin.system.user.store'), validUserPayload($groupId, ['email' => 'dupe@example.com']))
        ->assertInvalid('email');

    User::factory()->front()->create(['email' => 'shared@example.com']);
    $this->post(route('admin.system.user.store'), validUserPayload($groupId, ['email' => 'shared@example.com']))
        ->assertValid('email');

    User::factory()->create(['email' => 'gone@example.com'])->delete();
    $this->post(route('admin.system.user.store'), validUserPayload($groupId, ['email' => 'gone@example.com']))
        ->assertValid('email');
});

// ---------------------------------------------------------------- edit / update

test('edit redirects for a missing or soft-deleted user', function () {
    actingAsUserWithPermissions(['system.user.view']);

    $this->get(route('admin.system.user.edit', 999999))
        ->assertRedirect(route('admin.system.user.index'));

    $deleted = User::factory()->create();
    $deleted->delete();

    $this->get(route('admin.system.user.edit', $deleted->id))
        ->assertRedirect(route('admin.system.user.index'));
});

test('edit renders with can flags', function () {
    actingAsUserWithPermissions(['system.user.view']);
    $target = User::factory()->create();

    $this->get(route('admin.system.user.edit', $target->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/User/Edit')
            ->where('can.manage', false)
            ->where('isSelf', false)
        );
});

test('update saves the changes', function () {
    actingAsUserWithPermissions(['system.user.manage']);
    $target = User::factory()->create(['firstname' => 'Old']);

    $this->put(route('admin.system.user.update', $target->id), [
        'titlename' => 'คุณ',
        'firstname' => 'Updated',
        'lastname' => $target->lastname,
        'email' => $target->email,
        'usergroup_id' => superAdminGroupId(),
        'status' => 'Y',
    ])
        ->assertRedirect(route('admin.system.user.edit', $target->id))
        ->assertSessionHas('success');

    expect($target->fresh()->firstname)->toBe('Updated');
});

test('update blocks suspending your own account', function () {
    $me = actingAsUserWithPermissions(['system.user.manage']);

    $this->from(route('admin.system.user.edit', $me->id))
        ->put(route('admin.system.user.update', $me->id), [
            'titlename' => $me->titlename,
            'firstname' => $me->firstname,
            'lastname' => $me->lastname,
            'email' => $me->email,
            'usergroup_id' => $me->usergroup_id,
            'status' => 'N',
        ])
        ->assertInvalid('status');

    expect($me->fresh()->status)->toBe('Y');
});

// ---------------------------------------------------------------- destroy

test('destroy redirects without system.user.delete', function () {
    actingAsUserWithPermissions(['system.user.view', 'system.user.manage']);
    $target = User::factory()->create();

    $this->delete(route('admin.system.user.destroy', $target->id))
        ->assertRedirect(route('admin.system.user.index'));

    expect($target->fresh()->trashed())->toBeFalse();
});

test('destroy soft deletes a user', function () {
    actingAsUserWithPermissions(['system.user.delete']);
    $target = User::factory()->create();

    $this->delete(route('admin.system.user.destroy', $target->id))
        ->assertRedirect(route('admin.system.user.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($target);
});

test('destroy blocks deleting your own account', function () {
    $me = actingAsUserWithPermissions(['system.user.delete']);

    $this->from(route('admin.system.user.edit', $me->id))
        ->delete(route('admin.system.user.destroy', $me->id))
        ->assertInvalid('user');

    expect($me->fresh()->trashed())->toBeFalse();
});

// ---------------------------------------------------------------- password

test('password page redirects without system.user.password', function () {
    actingAsUserWithPermissions(['system.user.view']);
    $target = User::factory()->create();

    $this->get(route('admin.system.user.password', $target->id))
        ->assertRedirect(route('admin.system.user.index'));
});

test('password page renders with system.user.password', function () {
    actingAsUserWithPermissions(['system.user.password']);
    $target = User::factory()->create();

    $this->get(route('admin.system.user.password', $target->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/User/Password')
            ->where('user.id', $target->id)
        );
});

test('password update validates strength and confirmation', function () {
    actingAsUserWithPermissions(['system.user.password']);
    $target = User::factory()->create();

    $this->from(route('admin.system.user.password', $target->id))
        ->put(route('admin.system.user.password.update', $target->id), [
            'password' => 'weak',
            'password_confirmation' => 'nope',
        ])
        ->assertInvalid('password');
});

test('password update changes the password', function () {
    actingAsUserWithPermissions(['system.user.password']);
    $target = User::factory()->create();

    $this->put(route('admin.system.user.password.update', $target->id), [
        'password' => 'Aa1!aaaa',
        'password_confirmation' => 'Aa1!aaaa',
    ])
        ->assertRedirect(route('admin.system.user.edit', $target->id))
        ->assertSessionHas('success');

    expect(Hash::check('Aa1!aaaa', $target->fresh()->password))->toBeTrue();
});
