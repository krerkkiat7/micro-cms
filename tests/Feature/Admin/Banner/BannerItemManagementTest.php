<?php

use App\Models\BannerCategoryDetail;
use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\LogBackAction;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างหมวดหมู่ตัวอย่างมาด้วย (BannerSeeder) — เทสด้านล่างใช้หมวดหมู่แรกที่มีอยู่แล้วนี้
    $this->seed(DatabaseSeeder::class);
    $this->category = BannerCategoryInfo::query()->firstOrFail();
    // รูปภาพจำเป็นต้องกรอกตอนบันทึกป้ายโฆษณา — สร้างไฟล์ตัวอย่างไว้ให้เทสด้านล่างใช้เป็นค่าเริ่มต้น
    $this->image = FileInfo::create([
        'name' => 'banner.jpg', 'hash_name' => 'banner-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);
});

/**
 * @return array<string, mixed>
 */
function validBannerItemPayload(int $categoryId, array $overrides = []): array
{
    return array_replace_recursive([
        'banner_category_info_id' => $categoryId,
        'intro_image_id' => FileInfo::query()->where('hash_name', 'banner-hash.jpg')->value('id'),
        'link_type' => 'custom',
        'front_menu_info_id' => null,
        'url' => 'https://example.com',
        'link_target' => '_blank',
        'publish_date' => now()->format('Y-m-d H:i:s'),
        'publish_down' => null,
        'sort_order' => '1',
        'status' => 'Y',
        'detail' => [
            'th' => [
                'title' => 'ป้ายโฆษณาทดสอบ',
                'intro_text' => 'ข้อความเกริ่นนำภาษาไทย',
            ],
            'en' => [
                'title' => 'Test Banner',
                'intro_text' => 'English intro',
            ],
        ],
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without banner.item.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.banner.item.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders for a user with banner.item.view', function () {
    actingAsUserWithPermissions(['banner.item.view']);

    $this->get(route('admin.banner.item.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Banner/Item/Index')
            ->has('items.data')
            ->has('categories')
        );
});

test('index shows the default-language title and category, and filters by search term', function () {
    actingAsUserWithPermissions(['banner.item.view', 'banner.item.manage']);

    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));

    $this->get(route('admin.banner.item.index', ['q' => 'ป้ายโฆษณาทดสอบ']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.title', 'ป้ายโฆษณาทดสอบ')
            ->where('items.data.0.category_title', fn ($title) => $title !== null)
        );

    $this->get(route('admin.banner.item.index', ['q' => 'ไม่มีอยู่จริงแน่นอน']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});

test('index includes the intro image hash_name for the thumbnail column', function () {
    actingAsUserWithPermissions(['banner.item.view', 'banner.item.manage']);

    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));

    $this->get(route('admin.banner.item.index', ['q' => 'ป้ายโฆษณาทดสอบ']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.data.0.intro_image_hash_name', $this->image->hash_name)
        );
});

test('index filters by category', function () {
    actingAsUserWithPermissions(['banner.item.view', 'banner.item.manage']);

    // ข้อมูลตัวอย่างมีหมวดหมู่เดียว (Highlight) — สร้างหมวดที่สองเอง
    $otherCategory = BannerCategoryInfo::create(['status' => 'Y']);
    BannerCategoryDetail::create(['id' => $otherCategory->id, 'lang' => 'th', 'title' => 'หมวดที่สอง', 'status' => 'Y']);

    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id, [
        'detail' => ['th' => ['title' => 'ป้ายหมวดแรก'], 'en' => ['title' => 'First Cat']],
    ]));
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($otherCategory->id, [
        'detail' => ['th' => ['title' => 'ป้ายหมวดสอง'], 'en' => ['title' => 'Second Cat']],
    ]));

    $this->get(route('admin.banner.item.index', ['category_id' => $this->category->id, 'q' => 'ป้ายหมวด']))
        ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.title', 'ป้ายหมวดแรก'));
});

// ---------------------------------------------------------------- add / store

test('add page redirects without banner.item.manage', function () {
    actingAsUserWithPermissions(['banner.item.view']);

    $this->get(route('admin.banner.item.add'))
        ->assertRedirect(route('admin.banner.item.index'));
});

test('add page renders languages and categories', function () {
    actingAsUserWithPermissions(['banner.item.manage']);

    $this->get(route('admin.banner.item.add'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Banner/Item/Add')
            ->has('languages')
            ->has('categories')
        );
});

test('store requires the title only for the default language', function () {
    actingAsUserWithPermissions(['banner.item.manage']);

    $this->from(route('admin.banner.item.add'))
        ->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id, [
            'detail' => ['th' => ['title' => ''], 'en' => ['title' => '']],
        ]))
        ->assertInvalid(['detail.th.title'])
        ->assertValid(['detail.en.title']);
});

test('store requires a category, an image and a publish date', function () {
    actingAsUserWithPermissions(['banner.item.manage']);

    $this->from(route('admin.banner.item.add'))
        ->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id, [
            'banner_category_info_id' => null,
            'intro_image_id' => null,
            'publish_date' => null,
        ]))
        ->assertInvalid(['banner_category_info_id', 'intro_image_id', 'publish_date']);
});

test('store rejects a publish_down before publish_date', function () {
    actingAsUserWithPermissions(['banner.item.manage']);

    $this->from(route('admin.banner.item.add'))
        ->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id, [
            'publish_date' => '2026-01-10 00:00:00',
            'publish_down' => '2026-01-01 00:00:00',
        ]))
        ->assertInvalid(['publish_down']);
});

test('store rejects an invalid link_target', function () {
    actingAsUserWithPermissions(['banner.item.manage']);

    $this->from(route('admin.banner.item.add'))
        ->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id, [
            'link_target' => '_top',
        ]))
        ->assertInvalid(['link_target']);
});

test('store creates the banner with per-language details and logs the action', function () {
    $me = actingAsUserWithPermissions(['banner.item.manage']);

    $response = $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));

    $item = BannerItemInfo::query()->latest('id')->first();

    expect($item)->not->toBeNull()
        ->and($item->banner_category_info_id)->toBe($this->category->id)
        ->and($item->url)->toBe('https://example.com')
        ->and($item->link_target)->toBe('_blank')
        ->and($item->intro_image_id)->toBe($this->image->id)
        ->and($item->sort_order)->toBe(1)
        ->and($item->status)->toBe('Y')
        ->and($item->click_amount)->toBe(0)
        ->and($item->created_by)->toBe($me->id);

    $th = BannerItemDetail::where('id', $item->id)->where('lang', 'th')->first();
    $en = BannerItemDetail::where('id', $item->id)->where('lang', 'en')->first();

    expect($th->title)->toBe('ป้ายโฆษณาทดสอบ')
        ->and($en->title)->toBe('Test Banner');

    $response->assertRedirect(route('admin.banner.item.edit', $item->id))
        ->assertSessionHas('success');

    expect(LogBackAction::where('module_code', 'banner.item')
        ->where('action_type', 'create')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});

// ---------------------------------------------------------------- edit / update

test('edit redirects for a missing or soft-deleted banner', function () {
    actingAsUserWithPermissions(['banner.item.view']);

    $this->get(route('admin.banner.item.edit', 999999))
        ->assertRedirect(route('admin.banner.item.index'));

    actingAsUserWithPermissions(['banner.item.manage', 'banner.item.view', 'banner.item.delete']);
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));
    $item = BannerItemInfo::query()->latest('id')->first();
    $this->delete(route('admin.banner.item.destroy', $item->id));

    $this->get(route('admin.banner.item.edit', $item->id))
        ->assertRedirect(route('admin.banner.item.index'));
});

test('edit renders banner details and logs access + view action, without exposing click_amount', function () {
    actingAsUserWithPermissions(['banner.item.manage', 'banner.item.view']);
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));
    $item = BannerItemInfo::query()->latest('id')->first();

    $this->get(route('admin.banner.item.edit', $item->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Banner/Item/Edit')
            ->where('details.th.title', 'ป้ายโฆษณาทดสอบ')
            ->where('details.en.title', 'Test Banner')
            ->where('can.manage', true)
            ->where('can.delete', false)
            ->missing('item.click_amount')
        );

    expect(LogBackAction::where('module_code', 'banner.item')
        ->where('action_type', 'view')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});

test('edit exposes publish_date and publish_down formatted as datetime strings', function () {
    // กันบั๊กที่ BannerItemInfo ไม่ cast publish_date/publish_down เป็น datetime — ถ้าลืม cast
    // optional($model->publish_date)->format(...) จะคืน null เงียบ ๆ (Optional::__call เช็ก is_object())
    actingAsUserWithPermissions(['banner.item.manage', 'banner.item.view']);
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id, [
        'publish_date' => '2026-05-01 09:30:00',
        'publish_down' => '2026-06-01 18:00:00',
    ]));
    $item = BannerItemInfo::query()->latest('id')->first();

    $this->get(route('admin.banner.item.edit', $item->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('item.publish_date', '2026-05-01 09:30:00')
            ->where('item.publish_down', '2026-06-01 18:00:00')
        );
});

test('update saves changes to the info row and every language detail row', function () {
    $me = actingAsUserWithPermissions(['banner.item.manage']);
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));
    $item = BannerItemInfo::query()->latest('id')->first();

    $payload = validBannerItemPayload($this->category->id, [
        'url' => 'https://example.com/updated',
        'sort_order' => '9',
        'detail' => ['th' => ['title' => 'ชื่อใหม่'], 'en' => ['title' => 'Updated Name']],
    ]);

    $this->put(route('admin.banner.item.update', $item->id), $payload)
        ->assertRedirect(route('admin.banner.item.edit', $item->id))
        ->assertSessionHas('success');

    expect($item->fresh()->url)->toBe('https://example.com/updated')
        ->and($item->fresh()->sort_order)->toBe(9)
        ->and($item->fresh()->updated_by)->toBe($me->id);

    $th = BannerItemDetail::where('id', $item->id)->where('lang', 'th')->first();
    expect($th->title)->toBe('ชื่อใหม่')
        ->and($th->updated_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'banner.item')
        ->where('action_type', 'update')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});

test('update redirects without banner.item.manage', function () {
    actingAsUserWithPermissions(['banner.item.manage']);
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));
    $item = BannerItemInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['banner.item.view']);

    $this->put(route('admin.banner.item.update', $item->id), validBannerItemPayload($this->category->id))
        ->assertRedirect(route('admin.banner.item.index'));
});

// ---------------------------------------------------------------- destroy

test('destroy redirects without banner.item.delete', function () {
    actingAsUserWithPermissions(['banner.item.manage']);
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));
    $item = BannerItemInfo::query()->latest('id')->first();

    actingAsUserWithPermissions(['banner.item.view']);

    $this->delete(route('admin.banner.item.destroy', $item->id))
        ->assertRedirect(route('admin.banner.item.index'));

    expect($item->fresh()->trashed())->toBeFalse();
});

test('destroy soft deletes the banner and logs the action', function () {
    actingAsUserWithPermissions(['banner.item.manage']);
    $this->post(route('admin.banner.item.store'), validBannerItemPayload($this->category->id));
    $item = BannerItemInfo::query()->latest('id')->first();

    $me = actingAsUserWithPermissions(['banner.item.delete']);

    $this->delete(route('admin.banner.item.destroy', $item->id))
        ->assertRedirect(route('admin.banner.item.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($item);
    expect($item->fresh()->deleted_by)->toBe($me->id);

    expect(LogBackAction::where('module_code', 'banner.item')
        ->where('action_type', 'delete')
        ->where('ref_id', $item->id)
        ->exists())->toBeTrue();
});
