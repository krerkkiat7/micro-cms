<?php

use App\Models\SysAction;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function superAdminGroup(): UserGroup
{
    return UserGroup::where('name', 'Super Admin')->firstOrFail();
}

/**
 * @return array<string, mixed>
 */
function validGroupPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'บรรณาธิการ',
        'description' => 'กลุ่มทดสอบ',
        'status' => 'Y',
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without system.usergroup.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.usergroup.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders for a user with system.usergroup.view', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);

    $this->get(route('admin.system.usergroup.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/Usergroup/Index')
            ->has('groups.data')
        );
});

test('index search matches name and description', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);

    UserGroup::factory()->create(['name' => 'ฝ่ายข่าว', 'description' => 'ทีมข่าวสาร']);
    UserGroup::factory()->create(['name' => 'ฝ่ายการตลาด', 'description' => 'promotion']);

    $this->get(route('admin.system.usergroup.index', ['q' => 'ข่าว']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.data', fn ($rows) => collect($rows)->pluck('name')->contains('ฝ่ายข่าว')
                && collect($rows)->pluck('name')->doesntContain('ฝ่ายการตลาด')));
});

test('index defaults to sorting by group name ascending', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);

    UserGroup::factory()->create(['name' => 'zzz-กลุ่มท้าย']);
    UserGroup::factory()->create(['name' => 'aaa-กลุ่มต้น']);

    $this->get(route('admin.system.usergroup.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort', 'name')
            ->where('direction', 'asc')
            ->where('groups.data', function ($rows) {
                $names = collect($rows)->pluck('name');

                return $names->search('aaa-กลุ่มต้น') < $names->search('zzz-กลุ่มท้าย');
            }));
});

test('index filters by status', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);

    UserGroup::factory()->count(2)->create(['status' => 'Y']);
    UserGroup::factory()->inactive()->create();

    $this->get(route('admin.system.usergroup.index', ['status' => 'N']))
        ->assertInertia(fn (Assert $page) => $page->has('groups.data', 1));
});

test('index counts actions and back-office members per group', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);

    $group = UserGroup::factory()->create(['name' => 'กลุ่มนับ']);
    $group->actions()->attach(
        SysAction::whereIn('code', ['system.user.view', 'system.user.manage'])->pluck('id')
    );
    User::factory()->count(3)->create(['usergroup_id' => $group->id]);
    User::factory()->front()->create(['usergroup_id' => $group->id]);

    $this->get(route('admin.system.usergroup.index', ['q' => 'กลุ่มนับ']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.data.0.actions_count', 2)
            ->where('groups.data.0.users_count', 3));
});

// ---------------------------------------------------------------- add / store

test('add page redirects without system.usergroup.manage', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);

    $this->get(route('admin.system.usergroup.add'))
        ->assertRedirect(route('admin.system.usergroup.index'));
});

test('add page renders with system.usergroup.manage', function () {
    actingAsUserWithPermissions(['system.usergroup.manage']);

    $this->get(route('admin.system.usergroup.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/System/Usergroup/Add'));
});

test('store validates required fields', function () {
    actingAsUserWithPermissions(['system.usergroup.manage']);

    $this->from(route('admin.system.usergroup.add'))
        ->post(route('admin.system.usergroup.store'), [])
        ->assertInvalid(['name', 'status']);
});

test('store scopes name uniqueness to non-deleted groups', function () {
    actingAsUserWithPermissions(['system.usergroup.manage']);

    UserGroup::factory()->create(['name' => 'ซ้ำ']);

    $this->from(route('admin.system.usergroup.add'))
        ->post(route('admin.system.usergroup.store'), validGroupPayload(['name' => 'ซ้ำ']))
        ->assertInvalid('name');

    UserGroup::factory()->create(['name' => 'เคยลบ'])->delete();
    $this->post(route('admin.system.usergroup.store'), validGroupPayload(['name' => 'เคยลบ']))
        ->assertValid('name');
});

test('store creates a group with can_edit and can_delete defaulted to Y and redirects to edit', function () {
    actingAsUserWithPermissions(['system.usergroup.manage']);

    $response = $this->post(route('admin.system.usergroup.store'), validGroupPayload(['name' => 'กลุ่มใหม่']));

    $group = UserGroup::where('name', 'กลุ่มใหม่')->first();

    expect($group)->not->toBeNull()
        ->and($group->can_edit)->toBe('Y')
        ->and($group->can_delete)->toBe('Y');

    $response->assertRedirect(route('admin.system.usergroup.edit', $group->id))
        ->assertSessionHas('success');
});

// ---------------------------------------------------------------- edit / update

test('edit redirects for a missing or soft-deleted group', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);

    $this->get(route('admin.system.usergroup.edit', 999999))
        ->assertRedirect(route('admin.system.usergroup.index'));

    $deleted = UserGroup::factory()->create();
    $deleted->delete();

    $this->get(route('admin.system.usergroup.edit', $deleted->id))
        ->assertRedirect(route('admin.system.usergroup.index'));
});

test('update saves the changes', function () {
    actingAsUserWithPermissions(['system.usergroup.manage']);
    $group = UserGroup::factory()->create(['name' => 'เดิม']);

    $this->put(route('admin.system.usergroup.update', $group->id), validGroupPayload(['name' => 'ใหม่']))
        ->assertRedirect(route('admin.system.usergroup.edit', $group->id))
        ->assertSessionHas('success');

    expect($group->fresh()->name)->toBe('ใหม่');
});

test('update is blocked for a system group (can_edit = N)', function () {
    actingAsUserWithPermissions(['system.usergroup.manage']);
    $group = superAdminGroup();

    $this->from(route('admin.system.usergroup.edit', $group->id))
        ->put(route('admin.system.usergroup.update', $group->id), validGroupPayload(['name' => 'เปลี่ยนชื่อ']))
        ->assertInvalid('name');

    expect($group->fresh()->name)->toBe('Super Admin');
});

// ---------------------------------------------------------------- destroy

test('destroy redirects without system.usergroup.delete', function () {
    actingAsUserWithPermissions(['system.usergroup.view', 'system.usergroup.manage']);
    $group = UserGroup::factory()->create();

    $this->delete(route('admin.system.usergroup.destroy', $group->id))
        ->assertRedirect(route('admin.system.usergroup.index'));

    expect($group->fresh()->trashed())->toBeFalse();
});

test('destroy is blocked for a system group (can_delete = N)', function () {
    actingAsUserWithPermissions(['system.usergroup.delete']);
    $group = UserGroup::factory()->locked()->create();

    $this->from(route('admin.system.usergroup.edit', $group->id))
        ->delete(route('admin.system.usergroup.destroy', $group->id))
        ->assertInvalid('group');

    expect($group->fresh()->trashed())->toBeFalse();
});

test('destroy is blocked when the group still has back-office members', function () {
    actingAsUserWithPermissions(['system.usergroup.delete']);
    $group = UserGroup::factory()->create();
    User::factory()->create(['usergroup_id' => $group->id]);

    $this->from(route('admin.system.usergroup.edit', $group->id))
        ->delete(route('admin.system.usergroup.destroy', $group->id))
        ->assertInvalid('group');

    expect($group->fresh()->trashed())->toBeFalse();
});

test('destroy soft deletes an empty deletable group', function () {
    actingAsUserWithPermissions(['system.usergroup.delete']);
    $group = UserGroup::factory()->create();

    $this->delete(route('admin.system.usergroup.destroy', $group->id))
        ->assertRedirect(route('admin.system.usergroup.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($group);
});

// ---------------------------------------------------------------- rights

test('rights page redirects without system.usergroup.rights', function () {
    actingAsUserWithPermissions(['system.usergroup.view']);
    $group = UserGroup::factory()->create();

    $this->get(route('admin.system.usergroup.rights', $group->id))
        ->assertRedirect(route('admin.system.usergroup.index'));
});

test('rights page renders the action-group tree and the checked ids', function () {
    actingAsUserWithPermissions(['system.usergroup.rights']);

    $group = UserGroup::factory()->create();
    $checked = SysAction::whereIn('code', ['system.user.view', 'system.user.manage'])->pluck('id');
    $group->actions()->attach($checked);

    $this->get(route('admin.system.usergroup.rights', $group->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/Usergroup/Rights')
            ->where('group.id', $group->id)
            ->has('actionGroups')
            ->where('checkedIds', fn ($ids) => collect($ids)->sort()->values()->all() === $checked->sort()->values()->all()));
});

test('rights update replaces the group actions (real delete + re-insert)', function () {
    actingAsUserWithPermissions(['system.usergroup.rights']);

    $group = UserGroup::factory()->create();
    $group->actions()->attach(SysAction::where('code', 'article.item.view')->pluck('id'));

    $keep = SysAction::whereIn('code', ['system.user.view', 'system.user.manage'])->pluck('id')->all();

    $this->put(route('admin.system.usergroup.rights.update', $group->id), ['action_ids' => $keep])
        ->assertRedirect(route('admin.system.usergroup.rights', $group->id))
        ->assertSessionHas('success');

    expect($group->fresh()->actions->pluck('id')->sort()->values()->all())
        ->toEqual(collect($keep)->sort()->values()->all());
});

test('rights update drops actions whose parent is not selected', function () {
    actingAsUserWithPermissions(['system.usergroup.rights']);
    $group = UserGroup::factory()->create();

    // system.user.manage (system002) เป็นลูกของ system.user.view (system001)
    $childOnly = SysAction::where('code', 'system.user.manage')->pluck('id')->all();

    $this->put(route('admin.system.usergroup.rights.update', $group->id), ['action_ids' => $childOnly])
        ->assertRedirect(route('admin.system.usergroup.rights', $group->id));

    expect($group->fresh()->actions()->count())->toBe(0);
});

test('rights update clears every action when nothing is selected', function () {
    actingAsUserWithPermissions(['system.usergroup.rights']);
    $group = UserGroup::factory()->create();
    $group->actions()->attach(SysAction::where('code', 'system.user.view')->pluck('id'));

    $this->put(route('admin.system.usergroup.rights.update', $group->id), [])
        ->assertRedirect(route('admin.system.usergroup.rights', $group->id));

    expect($group->fresh()->actions()->count())->toBe(0);
});

test('rights update is blocked for a system group (can_edit = N)', function () {
    actingAsUserWithPermissions(['system.usergroup.rights']);
    $group = superAdminGroup();
    $before = $group->actions()->count();

    $this->from(route('admin.system.usergroup.rights', $group->id))
        ->put(route('admin.system.usergroup.rights.update', $group->id), ['action_ids' => []])
        ->assertInvalid('action_ids');

    expect($group->fresh()->actions()->count())->toBe($before);
});

test('rights update redirects without system.usergroup.rights', function () {
    actingAsUserWithPermissions(['system.usergroup.view', 'system.usergroup.manage']);
    $group = UserGroup::factory()->create();

    $this->put(route('admin.system.usergroup.rights.update', $group->id), ['action_ids' => []])
        ->assertRedirect(route('admin.system.usergroup.index'));
});
