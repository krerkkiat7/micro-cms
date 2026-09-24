<?php

use App\Models\LogBackAction;
use App\Models\SysTemplate;
use App\Models\SysTemplateAside;
use App\Models\SysTemplateBody;
use App\Models\SysTemplateFooter;
use App\Models\SysTemplateHeader;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TemplateSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้าง template ตัวอย่าง 3 รายการ (TemplateSeeder) — "Template องค์กร / หน่วยงาน" เป็นรายการที่ใช้งาน
    $this->seed(DatabaseSeeder::class);
});

function activeTemplate(): SysTemplate
{
    return SysTemplate::where('status', 'Y')->firstOrFail();
}

function inactiveTemplate(): SysTemplate
{
    return SysTemplate::where('status', 'N')->orderBy('id')->firstOrFail();
}

// ---------------------------------------------------------------- seeder

test('seeder creates sample templates with all zones and exactly one active', function () {
    expect(SysTemplate::count())->toBe(3)
        ->and(SysTemplate::where('status', 'Y')->count())->toBe(1)
        ->and(SysTemplateHeader::count())->toBe(3)
        ->and(SysTemplateBody::count())->toBe(3)
        ->and(SysTemplateFooter::count())->toBe(3)
        ->and(SysTemplateAside::count())->toBe(3);

    // รันซ้ำไม่สร้างแถวเพิ่ม
    $this->seed(TemplateSeeder::class);

    expect(SysTemplate::count())->toBe(3)
        ->and(SysTemplate::where('status', 'Y')->count())->toBe(1);
});

// ---------------------------------------------------------------- index

test('index redirects to dashboard without system.template.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.template.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index lists templates and filters by name and status', function () {
    actingAsUserWithPermissions(['system.template.view']);

    $this->get(route('admin.system.template.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/Template/Index')
            ->has('items.data', 3)
            ->where('can.manage', false)
        );

    $this->get(route('admin.system.template.index', ['q' => 'เรียบง่าย']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1));

    $this->get(route('admin.system.template.index', ['status' => 'Y']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1));
});

// ---------------------------------------------------------------- store

test('store creates a template with all four zones from the chosen preset', function () {
    actingAsUserWithPermissions(['system.template.manage']);

    $response = $this->post(route('admin.system.template.store'), [
        'name' => 'Template ทดสอบ',
        'preset' => 'minimal',
        'status' => 'N',
    ]);

    $template = SysTemplate::where('name', 'Template ทดสอบ')->firstOrFail();

    $response->assertRedirect(route('admin.system.template.layout', $template->id));

    expect($template->status)->toBe('N')
        ->and($template->preset)->toBe('minimal')
        ->and($template->header->layout_type)->toBe('main_only')
        ->and($template->header->sticky)->toBe('Y')
        ->and($template->body)->not->toBeNull()
        ->and($template->footer->layout_type)->toBe('site_contact_center')
        ->and($template->aside->display_type)->toBe('fullscreen')
        ->and(activeTemplate()->name)->toBe('Template องค์กร / หน่วยงาน');

    expect(LogBackAction::where('module_code', 'system.template')->where('action_type', 'create')->where('ref_id', $template->id)->exists())->toBeTrue();
});

test('store with status Y deactivates the currently active template', function () {
    actingAsUserWithPermissions(['system.template.manage']);

    $this->post(route('admin.system.template.store'), [
        'name' => 'Template ใหม่',
        'preset' => 'dark',
        'status' => 'Y',
    ]);

    expect(SysTemplate::where('status', 'Y')->pluck('name')->all())->toBe(['Template ใหม่']);
});

test('the first template is always activated when none is active', function () {
    SysTemplate::query()->delete();
    actingAsUserWithPermissions(['system.template.manage']);

    $this->post(route('admin.system.template.store'), [
        'name' => 'Template แรก',
        'preset' => 'classic',
        'status' => 'N',
    ]);

    expect(SysTemplate::where('name', 'Template แรก')->value('status'))->toBe('Y');
});

test('store validates name and preset', function () {
    actingAsUserWithPermissions(['system.template.manage']);

    $this->post(route('admin.system.template.store'), ['name' => '', 'preset' => 'unknown', 'status' => 'N'])
        ->assertSessionHasErrors(['name', 'preset']);
});

test('store is forbidden without system.template.manage', function () {
    actingAsUserWithPermissions(['system.template.view']);

    $this->post(route('admin.system.template.store'), ['name' => 'x', 'preset' => 'classic', 'status' => 'N'])
        ->assertRedirect(route('admin.system.template.index'));

    expect(SysTemplate::where('name', 'x')->exists())->toBeFalse();
});

// ---------------------------------------------------------------- update / activate

test('update renames and activating deactivates the others', function () {
    actingAsUserWithPermissions(['system.template.manage']);
    $template = inactiveTemplate();

    $this->put(route('admin.system.template.update', $template->id), ['name' => 'ชื่อใหม่', 'status' => 'Y'])
        ->assertRedirect(route('admin.system.template.edit', $template->id));

    expect($template->fresh()->name)->toBe('ชื่อใหม่')
        ->and(SysTemplate::where('status', 'Y')->pluck('id')->all())->toBe([$template->id]);
});

test('the active template cannot be switched off directly', function () {
    actingAsUserWithPermissions(['system.template.manage']);
    $template = activeTemplate();

    $this->put(route('admin.system.template.update', $template->id), ['name' => $template->name, 'status' => 'N'])
        ->assertSessionHasErrors('status');

    expect($template->fresh()->status)->toBe('Y');
});

test('activate switches the active template', function () {
    actingAsUserWithPermissions(['system.template.manage']);
    $previous = activeTemplate();
    $template = inactiveTemplate();

    $this->put(route('admin.system.template.activate', $template->id))->assertRedirect();

    expect($template->fresh()->status)->toBe('Y')
        ->and($previous->fresh()->status)->toBe('N');
});

// ---------------------------------------------------------------- destroy

test('destroy soft deletes an inactive template and records the actor', function () {
    $user = actingAsUserWithPermissions(['system.template.delete']);
    $template = inactiveTemplate();

    $this->delete(route('admin.system.template.destroy', $template->id))
        ->assertRedirect(route('admin.system.template.index'));

    $this->assertSoftDeleted('sys_template', ['id' => $template->id, 'deleted_by' => $user->id]);
    expect(LogBackAction::where('module_code', 'system.template')->where('action_type', 'delete')->where('ref_id', $template->id)->exists())->toBeTrue();
});

test('the active template cannot be deleted', function () {
    actingAsUserWithPermissions(['system.template.delete']);
    $template = activeTemplate();

    $this->delete(route('admin.system.template.destroy', $template->id))
        ->assertSessionHasErrors('delete');

    expect($template->fresh()->trashed())->toBeFalse();
});

// ---------------------------------------------------------------- edit

test('edit renders the general tab and logs a view action', function () {
    actingAsUserWithPermissions(['system.template.view']);
    $template = activeTemplate();

    $this->get(route('admin.system.template.edit', $template->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/Template/Edit')
            ->where('template.id', $template->id)
            ->where('template.preset_label', 'องค์กร / หน่วยงาน')
        );

    expect(LogBackAction::where('module_code', 'system.template')->where('action_type', 'view')->where('ref_id', $template->id)->exists())->toBeTrue();
});
