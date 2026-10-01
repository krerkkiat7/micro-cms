<?php

use App\Models\FileInfo;
use App\Models\FrontMenuInfo;
use App\Models\LogBackAction;
use App\Models\PopupItemInfo;
use App\Models\PopupItemPart;
use App\Models\PopupItemPartDetail;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    // popup ตัวอย่าง (PopupSeeder) — เทสนับ/อ่าน popup ที่สร้างเองเท่านั้น
    PopupItemInfo::query()->forceDelete();
    $this->image = FileInfo::create([
        'name' => 'popup.jpg', 'hash_name' => 'popup-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);
});

/**
 * @return array<string, mixed>
 */
function validPopupPayload(array $overrides = []): array
{
    return array_replace([
        'name' => 'Popup ทดสอบ',
        'display_type' => 'modal',
        'show_dismiss_today' => 'Y',
        'show_arrows' => 'Y',
        'show_dots' => 'Y',
        'autoplay' => 'Y',
        'slide_interval' => '5',
        'slide_speed' => '3000',
        'menu_mode' => 'all',
        'menu_ids' => [],
        'publish_date' => now()->format('Y-m-d H:i:s'),
        'publish_down' => null,
        'sort_order' => '0',
        'status' => 'Y',
        'parts' => [
            [
                'part_type' => 'image_text',
                'image_id' => FileInfo::query()->where('hash_name', 'popup-hash.jpg')->value('id'),
                'image_size' => 'full',
                'url' => 'https://example.com',
                'link_target' => '_blank',
                'status' => 'Y',
                'detail' => ['th' => '<p>ข้อความไทย</p>', 'en' => '<p>English</p>'],
            ],
        ],
    ], $overrides);
}

function textPart(array $overrides = []): array
{
    return array_replace([
        'part_type' => 'text',
        'image_id' => null,
        'image_size' => 'full',
        'url' => '',
        'link_target' => '_self',
        'status' => 'Y',
        'detail' => ['th' => '<p>ข้อความ</p>'],
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without popup.item.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.popup.item.index'))->assertRedirect(route('admin.dashboard'));
});

test('index lists popups and filters by name and status', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);

    $this->post(route('admin.popup.item.store'), validPopupPayload(['name' => 'ประกาศวันหยุด']));
    $this->post(route('admin.popup.item.store'), validPopupPayload(['name' => 'โปรโมชั่น', 'status' => 'N']));

    $this->get(route('admin.popup.item.index', ['q' => 'วันหยุด']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Popup/Item/Index')
            ->has('items.data', 1)
            ->where('items.data.0.name', 'ประกาศวันหยุด')
        );

    $this->get(route('admin.popup.item.index', ['status' => 'N']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.name', 'โปรโมชั่น')
        );
});

// ---------------------------------------------------------------- add / store

test('add page redirects without popup.item.manage', function () {
    actingAsUserWithPermissions(['popup.item.view']);

    $this->get(route('admin.popup.item.add'))->assertRedirect(route('admin.popup.item.index'));
});

test('add page renders languages and the front menu tree', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);

    $this->get(route('admin.popup.item.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Popup/Item/Add')
            ->has('languages')
            ->has('menuTree')
        );
});

test('store creates the popup with parts, details and logs the action', function () {
    $user = actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);

    $this->post(route('admin.popup.item.store'), validPopupPayload([
        'parts' => [validPopupPayload()['parts'][0], textPart(['status' => 'N'])],
    ]))->assertRedirect()->assertSessionHas('success');

    $popup = PopupItemInfo::query()->firstOrFail();
    expect($popup->name)->toBe('Popup ทดสอบ')
        ->and($popup->created_by)->toBe($user->id)
        ->and($popup->parts)->toHaveCount(2);

    $first = $popup->parts[0];
    expect($first->part_type)->toBe('image_text')
        ->and($first->image_id)->toBe($this->image->id)
        ->and($first->sort_order)->toBe(0)
        ->and(PopupItemPartDetail::where('id', $first->id)->count())->toBe(2)
        ->and($popup->parts[1]->status)->toBe('N');

    expect(LogBackAction::where('module_code', 'popup.item')->where('action_type', 'create')->where('ref_id', $popup->id)->exists())->toBeTrue();
});

test('store requires a name, a publish date and at least one part', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);

    $this->post(route('admin.popup.item.store'), validPopupPayload(['name' => '', 'publish_date' => null, 'parts' => []]))
        ->assertSessionHasErrors(['name', 'publish_date', 'parts']);
});

test('store requires at least one shown part', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);

    $this->post(route('admin.popup.item.store'), validPopupPayload(['parts' => [textPart(['status' => 'N'])]]))
        ->assertSessionHasErrors('parts');

    expect(PopupItemInfo::count())->toBe(0);
});

test('store requires an image for image parts and default-language text for text parts', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);

    $this->post(route('admin.popup.item.store'), validPopupPayload(['parts' => [
        ['part_type' => 'image', 'image_id' => null, 'image_size' => 'full', 'link_target' => '_blank', 'status' => 'Y'],
        textPart(['detail' => ['th' => '<p></p>', 'en' => '<p>only english</p>']]),
    ]]))->assertSessionHasErrors(['parts.0.image_id', 'parts.1.detail.th']);
});

test('a floating popup accepts image parts only', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);
    $imageId = FileInfo::query()->where('hash_name', 'popup-hash.jpg')->value('id');
    $imagePart = ['part_type' => 'image', 'image_id' => $imageId, 'image_size' => 'medium', 'link_target' => '_blank', 'status' => 'Y'];

    $this->post(route('admin.popup.item.store'), validPopupPayload(['display_type' => 'floating', 'parts' => [$imagePart, textPart()]]))
        ->assertSessionHasErrors('parts.1.part_type');

    $this->post(route('admin.popup.item.store'), validPopupPayload(['display_type' => 'floating', 'parts' => [$imagePart]]))
        ->assertSessionHasNoErrors();

    expect(PopupItemInfo::query()->value('display_type'))->toBe('floating');
});

test('store requires menus when showing on selected menus, and only content menus are accepted', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);

    $this->post(route('admin.popup.item.store'), validPopupPayload(['menu_mode' => 'selected', 'menu_ids' => []]))
        ->assertSessionHasErrors('menu_ids');

    $heading = FrontMenuInfo::create(['menu_type' => 'heading', 'status' => 'Y', 'sort_order' => 99]);

    $this->post(route('admin.popup.item.store'), validPopupPayload(['menu_mode' => 'selected', 'menu_ids' => [$heading->id]]))
        ->assertSessionHasErrors('menu_ids.0');
});

test('store saves selected menus, and switching to all pages clears them', function () {
    $user = actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);
    $menu = FrontMenuInfo::query()->where('menu_type', 'page')->firstOrFail();

    $this->post(route('admin.popup.item.store'), validPopupPayload(['menu_mode' => 'selected', 'menu_ids' => [$menu->id]]));

    $popup = PopupItemInfo::query()->firstOrFail();
    expect($popup->menus()->pluck('front_menu_info.id')->all())->toBe([$menu->id])
        ->and($popup->menus()->first()->pivot->created_by)->toBe($user->id);

    $this->put(route('admin.popup.item.update', $popup->id), validPopupPayload(['menu_mode' => 'all', 'menu_ids' => [$menu->id]]));

    expect($popup->menus()->count())->toBe(0);
});

// ---------------------------------------------------------------- edit / update / destroy

test('edit renders the popup with parts, menus and system info and logs the view', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);
    $this->post(route('admin.popup.item.store'), validPopupPayload());
    $popup = PopupItemInfo::query()->firstOrFail();

    $this->get(route('admin.popup.item.edit', $popup->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Popup/Item/Edit')
            ->where('item.name', 'Popup ทดสอบ')
            ->has('item.parts', 1)
            ->where('item.parts.0.image.id', $this->image->id)
            ->where('item.parts.0.detail.th', '<p>ข้อความไทย</p>')
            ->has('systemInfo')
            ->has('menuTree')
        );

    expect(LogBackAction::where('module_code', 'popup.item')->where('action_type', 'view')->exists())->toBeTrue();
});

test('update replaces parts and records updated_by', function () {
    $user = actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);
    $this->post(route('admin.popup.item.store'), validPopupPayload());
    $popup = PopupItemInfo::query()->firstOrFail();
    $oldPartId = $popup->parts()->value('id');

    $this->put(route('admin.popup.item.update', $popup->id), validPopupPayload([
        'name' => 'ชื่อใหม่',
        'show_arrows' => 'N',
        'parts' => [textPart(), textPart(['detail' => ['th' => '<p>ที่สอง</p>']])],
    ]))->assertRedirect(route('admin.popup.item.edit', $popup->id));

    $popup->refresh();
    expect($popup->name)->toBe('ชื่อใหม่')
        ->and($popup->show_arrows)->toBe('N')
        ->and($popup->updated_by)->toBe($user->id)
        ->and($popup->parts()->count())->toBe(2)
        ->and(PopupItemPart::withTrashed()->find($oldPartId)->trashed())->toBeTrue();

    expect(LogBackAction::where('module_code', 'popup.item')->where('action_type', 'update')->exists())->toBeTrue();
});

test('destroy soft deletes the popup with deleted_by', function () {
    $user = actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage', 'popup.item.delete']);
    $this->post(route('admin.popup.item.store'), validPopupPayload());
    $popup = PopupItemInfo::query()->firstOrFail();

    $this->delete(route('admin.popup.item.destroy', $popup->id))->assertRedirect(route('admin.popup.item.index'));

    $this->assertSoftDeleted('popup_item_info', ['id' => $popup->id, 'deleted_by' => $user->id]);
    expect(LogBackAction::where('module_code', 'popup.item')->where('action_type', 'delete')->exists())->toBeTrue();
});

test('destroy redirects without popup.item.delete', function () {
    actingAsUserWithPermissions(['popup.item.view', 'popup.item.manage']);
    $this->post(route('admin.popup.item.store'), validPopupPayload());
    $popup = PopupItemInfo::query()->firstOrFail();

    $this->delete(route('admin.popup.item.destroy', $popup->id))->assertRedirect(route('admin.popup.item.index'));

    expect($popup->fresh()->trashed())->toBeFalse();
});
