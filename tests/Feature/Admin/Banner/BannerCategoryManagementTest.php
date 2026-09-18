<?php

use App\Models\BannerCategoryDetail;
use App\Models\BannerCategoryInfo;
use App\Models\BannerItemInfo;
use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างหมวดหมู่ตัวอย่างมาด้วย (BannerSeeder) — เทสด้านล่างจึงค้นหา/กรองด้วยคำเฉพาะของเทสเอง
    // ไม่พึ่งจำนวนแถวทั้งหมด และภาษาระบบที่ seed คือ th (หลัก) + en
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validBannerCategoryPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'detail' => [
            'th' => [
                'title' => 'หมวดหมู่ทดสอบ',
                'intro_text' => 'ข้อความเกริ่นนำภาษาไทย',
            ],
            'en' => [
                'title' => 'Test Category',
                'intro_text' => 'English intro',
            ],
        ],
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without banner.category.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.banner.category.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders for a user with banner.category.view', function () {
    actingAsUserWithPermissions(['banner.category.view']);

    $this->get(route('admin.banner.category.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Banner/Category/Index')
            ->has('categories.data')
        );
});

test('index shows the default-language title and filters by search term', function () {
    actingAsUserWithPermissions(['banner.category.view', 'banner.category.manage']);

    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());

    $this->get(route('admin.banner.category.index', ['q' => 'หมวดหมู่ทดสอบ']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories.data', 1)
            ->where('categories.data.0.title', 'หมวดหมู่ทดสอบ')
        );

    $this->get(route('admin.banner.category.index', ['q' => 'ไม่มีอยู่จริงแน่นอน']))
        ->assertInertia(fn (Assert $page) => $page->has('categories.data', 0));
});

test('index counts banners belonging to each category', function () {
    actingAsUserWithPermissions(['banner.category.view', 'banner.category.manage']);

    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());
    $category = BannerCategoryInfo::query()->latest('id')->first();

    BannerItemInfo::create(['banner_category_info_id' => $category->id, 'status' => 'Y']);

    $this->get(route('admin.banner.category.index', ['q' => 'หมวดหมู่ทดสอบ']))
        ->assertInertia(fn (Assert $page) => $page->where('categories.data.0.banner_count', 1));
});

test('index filters by status', function () {
    actingAsUserWithPermissions(['banner.category.view', 'banner.category.manage']);

    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload([
        'status' => 'N',
        'detail' => ['th' => ['title' => 'หมวดหมู่ปิดใช้งาน'], 'en' => ['title' => 'Disabled Category']],
    ]));

    $this->get(route('admin.banner.category.index', ['q' => 'หมวดหมู่ปิดใช้งาน', 'status' => 'N']))
        ->assertInertia(fn (Assert $page) => $page->has('categories.data', 1));

    $this->get(route('admin.banner.category.index', ['q' => 'หมวดหมู่ปิดใช้งาน', 'status' => 'Y']))
        ->assertInertia(fn (Assert $page) => $page->has('categories.data', 0));
});

// ---------------------------------------------------------------- add / store

test('add page redirects without banner.category.manage', function () {
    actingAsUserWithPermissions(['banner.category.view']);

    $this->get(route('admin.banner.category.add'))
        ->assertRedirect(route('admin.banner.category.index'));
});

test('add page renders with the system languages', function () {
    actingAsUserWithPermissions(['banner.category.manage']);

    $this->get(route('admin.banner.category.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Banner/Category/Add')
            ->where('languages', [
                ['code' => 'th', 'is_default' => true],
                ['code' => 'en', 'is_default' => false],
            ])
        );
});

test('languages are ordered with the default language first, then alphabetically', function () {
    actingAsUserWithPermissions(['banner.category.manage']);

    SysSetting::where('group', 'site')->where('name', 'lang_selected')->update(['value' => 'en,zh,th']);
    SysSetting::where('group', 'site')->where('name', 'lang_default')->update(['value' => 'th']);
    Setting::forget('site');

    $this->get(route('admin.banner.category.add'))
        ->assertInertia(fn (Assert $page) => $page->where('languages', [
            ['code' => 'th', 'is_default' => true],
            ['code' => 'en', 'is_default' => false],
            ['code' => 'zh', 'is_default' => false],
        ]));
});

test('store requires the title only for the default language', function () {
    actingAsUserWithPermissions(['banner.category.manage']);

    $this->from(route('admin.banner.category.add'))
        ->post(route('admin.banner.category.store'), validBannerCategoryPayload([
            'detail' => ['th' => ['title' => ''], 'en' => ['title' => '']],
        ]))
        ->assertInvalid(['detail.th.title'])
        ->assertValid(['detail.en.title']);
});

test('store creates the category with per-language details and logs the action', function () {
    $me = actingAsUserWithPermissions(['banner.category.manage']);

    $response = $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());

    $category = BannerCategoryInfo::query()->latest('id')->first();

    expect($category)->not->toBeNull()
        ->and($category->status)->toBe('Y')
        ->and($category->created_by)->toBe($me->id);

    $th = BannerCategoryDetail::where('id', $category->id)->where('lang', 'th')->first();
    $en = BannerCategoryDetail::where('id', $category->id)->where('lang', 'en')->first();

    expect($th->title)->toBe('หมวดหมู่ทดสอบ')
        ->and($en->title)->toBe('Test Category')
        ->and($en->created_by)->toBe($me->id);

    $response->assertRedirect(route('admin.banner.category.edit', $category->id))
        ->assertSessionHas('success');

    expect(LogBackAction::where('module_code', 'banner.category')
        ->where('action_type', 'create')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});

// ---------------------------------------------------------------- edit / update

test('edit redirects for a missing or soft-deleted category', function () {
    actingAsUserWithPermissions(['banner.category.view']);

    $this->get(route('admin.banner.category.edit', 999999))
        ->assertRedirect(route('admin.banner.category.index'));

    actingAsUserWithPermissions(['banner.category.manage', 'banner.category.view', 'banner.category.delete']);
    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());
    $category = BannerCategoryInfo::query()->latest('id')->first();
    $this->delete(route('admin.banner.category.destroy', $category->id));

    $this->get(route('admin.banner.category.edit', $category->id))
        ->assertRedirect(route('admin.banner.category.index'));
});

test('edit renders category details keyed by language and logs access + view action', function () {
    actingAsUserWithPermissions(['banner.category.manage', 'banner.category.view']);
    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());
    $category = BannerCategoryInfo::query()->latest('id')->first();

    $this->get(route('admin.banner.category.edit', $category->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Banner/Category/Edit')
            ->where('details.th.title', 'หมวดหมู่ทดสอบ')
            ->where('details.en.title', 'Test Category')
            ->where('can.manage', true)
            ->where('can.delete', false)
        );

    expect(LogBackAction::where('module_code', 'banner.category')
        ->where('action_type', 'view')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});

test('update saves changes to the info row and every language detail row', function () {
    $me = actingAsUserWithPermissions(['banner.category.manage']);
    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());
    $category = BannerCategoryInfo::query()->latest('id')->first();

    $payload = validBannerCategoryPayload([
        'status' => 'N',
        'detail' => ['th' => ['title' => 'ชื่อใหม่'], 'en' => ['title' => 'Updated Name']],
    ]);

    $this->put(route('admin.banner.category.update', $category->id), $payload)
        ->assertRedirect(route('admin.banner.category.edit', $category->id))
        ->assertSessionHas('success');

    expect($category->fresh()->status)->toBe('N')
        ->and($category->fresh()->updated_by)->toBe($me->id);

    $th = BannerCategoryDetail::where('id', $category->id)->where('lang', 'th')->first();
    expect($th->title)->toBe('ชื่อใหม่')
        ->and($th->updated_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'banner.category')
        ->where('action_type', 'update')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});

test('update redirects without banner.category.manage', function () {
    actingAsUserWithPermissions(['banner.category.manage']);
    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());
    $category = BannerCategoryInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['banner.category.view']);

    $this->put(route('admin.banner.category.update', $category->id), validBannerCategoryPayload())
        ->assertRedirect(route('admin.banner.category.index'));
});

// ---------------------------------------------------------------- destroy

test('destroy redirects without banner.category.delete', function () {
    actingAsUserWithPermissions(['banner.category.manage']);
    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());
    $category = BannerCategoryInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['banner.category.view']);

    $this->delete(route('admin.banner.category.destroy', $category->id))
        ->assertRedirect(route('admin.banner.category.index'));

    expect($category->fresh()->trashed())->toBeFalse();
});

test('destroy soft deletes the category and logs the action', function () {
    actingAsUserWithPermissions(['banner.category.manage']);
    $this->post(route('admin.banner.category.store'), validBannerCategoryPayload());
    $category = BannerCategoryInfo::query()->latest('id')->first();

    $me = actingAsUserWithPermissions(['banner.category.delete']);

    $this->delete(route('admin.banner.category.destroy', $category->id))
        ->assertRedirect(route('admin.banner.category.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($category);
    expect($category->fresh()->deleted_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'banner.category')
        ->where('action_type', 'delete')
        ->where('ref_id', $category->id)
        ->exists())->toBeTrue();
});
