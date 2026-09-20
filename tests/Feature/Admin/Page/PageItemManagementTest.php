<?php

use App\Models\FileInfo;
use App\Models\LogBackAction;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างหน้าเพจตัวอย่างมาด้วย (PageSeeder) — เทสด้านล่างสร้างหน้าใหม่ของตัวเองผ่าน store
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validPageItemPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'intro_image_id' => null,
        'background_color' => 'transparent',
        'background_image_id' => null,
        'background_repeat' => null,
        'background_size' => null,
        'background_attachment' => null,
        'background_position' => null,
        'status' => 'Y',
        'detail' => [
            'th' => [
                'title' => 'หน้าทดสอบ',
                'intro_text' => 'ข้อความเกริ่นนำภาษาไทย',
                'slug' => 'test-page-th',
            ],
            'en' => [
                'title' => 'Test Page',
                'intro_text' => 'English intro',
                'slug' => 'test-page-en',
            ],
        ],
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without page.item.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.page.item.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders the seeded sample page and filters by search term', function () {
    actingAsUserWithPermissions(['page.item.view']);

    $this->get(route('admin.page.item.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Page/Item/Index')
            ->has('items.data', 1)
            ->where('items.data.0.title', 'หน้าเพจตัวอย่าง')
            ->has('items.data.0.created_at')
        );

    $this->get(route('admin.page.item.index', ['q' => 'ไม่มีอยู่จริงแน่นอน']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});

test('index filters by status', function () {
    actingAsUserWithPermissions(['page.item.view']);

    $this->get(route('admin.page.item.index', ['status' => 'N']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});

// ---------------------------------------------------------------- add / store

test('add page redirects without page.item.manage', function () {
    actingAsUserWithPermissions(['page.item.view']);

    $this->get(route('admin.page.item.add'))
        ->assertRedirect(route('admin.page.item.index'));
});

test('add page renders with languages', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->get(route('admin.page.item.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Page/Item/Add')->has('languages'));
});

test('store requires the title only for the default language', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->from(route('admin.page.item.add'))
        ->post(route('admin.page.item.store'), validPageItemPayload([
            'detail' => ['th' => ['title' => ''], 'en' => ['title' => '']],
        ]))
        ->assertInvalid(['detail.th.title'])
        ->assertValid(['detail.en.title']);
});

test('store rejects a malformed background colour', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->from(route('admin.page.item.add'))
        ->post(route('admin.page.item.store'), validPageItemPayload(['background_color' => 'red; x']))
        ->assertInvalid(['background_color']);
});

test('store rejects a file that does not exist', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->from(route('admin.page.item.add'))
        ->post(route('admin.page.item.store'), validPageItemPayload(['intro_image_id' => 999999]))
        ->assertInvalid(['intro_image_id']);
});

test('store creates the page with per-language details and logs the action', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);
    $image = FileInfo::create([
        'name' => 'cover.jpg', 'hash_name' => 'cover-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $response = $this->post(route('admin.page.item.store'), validPageItemPayload(['intro_image_id' => $image->id]));

    $item = PageItemInfo::query()->latest('id')->first();

    expect($item->intro_image_id)->toBe($image->id)
        ->and($item->background_color)->toBe('transparent')
        ->and($item->status)->toBe('Y')
        ->and($item->is_temp)->toBe('N')
        ->and($item->created_by)->toBe($me->id);

    expect(PageItemDetail::where('id', $item->id)->where('lang', 'th')->value('title'))->toBe('หน้าทดสอบ')
        ->and(PageItemDetail::where('id', $item->id)->where('lang', 'en')->value('slug'))->toBe('test-page-en');

    $response->assertRedirect(route('admin.page.item.edit', $item->id))
        ->assertSessionHas('success');

    expect(LogBackAction::where('module_code', 'page.item')->where('action_type', 'create')->where('ref_id', $item->id)->exists())->toBeTrue();
});

test('store rejects a duplicate slug within the same language', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->post(route('admin.page.item.store'), validPageItemPayload());

    $this->from(route('admin.page.item.add'))
        ->post(route('admin.page.item.store'), validPageItemPayload())
        ->assertInvalid(['detail.th.slug', 'detail.en.slug']);
});

// ---------------------------------------------------------------- edit / update

test('edit redirects without page.item.view and for a missing page', function () {
    actingAsUserWithPermissions([]);
    $this->get(route('admin.page.item.edit', 1))->assertRedirect(route('admin.page.item.index'));

    actingAsUserWithPermissions(['page.item.view']);
    $this->get(route('admin.page.item.edit', 999999))->assertRedirect(route('admin.page.item.index'));
});

test('edit renders the page data and logs a view action', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $item = PageItemInfo::query()->firstOrFail();

    $this->get(route('admin.page.item.edit', $item->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Page/Item/Edit')
            ->where('item.id', $item->id)
            ->where('details.th.title', 'หน้าเพจตัวอย่าง')
            ->where('can.manage', false)
        );

    expect(LogBackAction::where('module_code', 'page.item')->where('action_type', 'view')->where('ref_id', $item->id)->exists())->toBeTrue();
});

test('update saves changes and keeps the languages independent', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);
    $this->post(route('admin.page.item.store'), validPageItemPayload());
    $item = PageItemInfo::query()->latest('id')->first();

    $payload = validPageItemPayload([
        'status' => 'N',
        'detail' => ['th' => ['title' => 'ชื่อใหม่'], 'en' => ['title' => 'New Title']],
    ]);

    $this->put(route('admin.page.item.update', $item->id), $payload)
        ->assertRedirect(route('admin.page.item.edit', $item->id));

    expect($item->fresh()->status)->toBe('N')
        ->and($item->fresh()->updated_by)->toBe($me->id)
        ->and(PageItemDetail::where('id', $item->id)->where('lang', 'th')->value('title'))->toBe('ชื่อใหม่')
        ->and(PageItemDetail::where('id', $item->id)->where('lang', 'en')->value('title'))->toBe('New Title');

    // บันทึกซ้ำด้วยค่าเดิมทันที (ค่าไม่เปลี่ยน) ต้องไม่พัง
    $this->put(route('admin.page.item.update', $item->id), $payload)
        ->assertRedirect(route('admin.page.item.edit', $item->id));

    expect(LogBackAction::where('module_code', 'page.item')->where('action_type', 'update')->exists())->toBeTrue();
});

test('update does not treat the page own slug as a duplicate', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $this->post(route('admin.page.item.store'), validPageItemPayload());
    $item = PageItemInfo::query()->latest('id')->first();

    $this->from(route('admin.page.item.edit', $item->id))
        ->put(route('admin.page.item.update', $item->id), validPageItemPayload())
        ->assertValid();
});

test('update redirects without page.item.manage', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $item = PageItemInfo::query()->firstOrFail();

    $this->put(route('admin.page.item.update', $item->id), validPageItemPayload())
        ->assertRedirect(route('admin.page.item.index'));
});

// ---------------------------------------------------------------- destroy

test('destroy requires page.item.delete', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $item = PageItemInfo::query()->firstOrFail();

    $this->delete(route('admin.page.item.destroy', $item->id))
        ->assertRedirect(route('admin.page.item.index'));

    $this->assertNotSoftDeleted($item);
});

test('destroy soft deletes the page, records deleted_by, logs, and frees the slug for reuse', function () {
    $me = actingAsUserWithPermissions(['page.item.manage', 'page.item.delete']);
    $this->post(route('admin.page.item.store'), validPageItemPayload());
    $item = PageItemInfo::query()->latest('id')->first();

    $this->delete(route('admin.page.item.destroy', $item->id))
        ->assertRedirect(route('admin.page.item.index'));

    $this->assertSoftDeleted($item);
    expect(PageItemInfo::withTrashed()->find($item->id)->deleted_by)->toBe($me->id);
    expect(LogBackAction::where('module_code', 'page.item')->where('action_type', 'delete')->where('ref_id', $item->id)->exists())->toBeTrue();

    // slug เดิมต้องใช้กับหน้าใหม่ได้ (ทั้ง validation และ unique index ระดับ DB)
    $this->post(route('admin.page.item.store'), validPageItemPayload())->assertSessionHasNoErrors();
    expect(PageItemInfo::query()->latest('id')->first()->id)->not->toBe($item->id);
});
