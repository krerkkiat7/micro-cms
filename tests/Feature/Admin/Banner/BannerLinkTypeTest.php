<?php

use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\FrontMenuInfo;
use App\Support\Front\FrontCache;
use App\Support\PageWidget\PageWidgetRegistry;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// ลิงก์ของป้ายโฆษณาแบบเลือกประเภทก่อน (ไม่มีลิงก์ / เมนู / กำหนดเอง) — เทียบเคียงปุ่ม "อ่านทั้งหมด" ของ widget

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->category = BannerCategoryInfo::query()->firstOrFail();
    $this->image = FileInfo::create(['name' => 'b.jpg', 'hash_name' => 'link-banner.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);
    $this->pageMenu = FrontMenuInfo::query()->where('status', 'Y')->where('menu_type', 'page')->firstOrFail();
});

function bannerLinkPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'banner_category_info_id' => test()->category->id,
        'intro_image_id' => test()->image->id,
        'link_type' => 'none',
        'front_menu_info_id' => null,
        'url' => '',
        'link_target' => '_self',
        'publish_date' => now()->subMinute()->format('Y-m-d H:i:s'),
        'publish_down' => null,
        'sort_order' => '0',
        'status' => 'Y',
        'detail' => ['th' => ['title' => 'ป้ายลิงก์', 'intro_text' => ''], 'en' => ['title' => 'Link banner', 'intro_text' => '']],
    ], $overrides);
}

/**
 * ลิงก์ที่หน้าบ้านได้รับของป้ายโฆษณาในหมวดหมู่ทดสอบ (ผ่าน widget Grid จาก banner)
 *
 * @return array{url: string|null, link_target: string}
 */
function bannerFrontLink(int $bannerId): array
{
    $widget = PageWidgetRegistry::find('gridbanner');
    $items = $widget->frontItems(array_replace($widget->defaults(), ['banner_category_info_id' => test()->category->id]), 'th');
    $item = collect($items)->firstWhere('id', $bannerId);

    return ['url' => $item['url'], 'link_target' => $item['link_target']];
}

test('the add and edit forms receive the front menu options', function () {
    actingAsUserWithPermissions(['banner.item.view', 'banner.item.manage']);

    $this->get(route('admin.banner.item.add'))
        ->assertInertia(fn (Assert $page) => $page->has('frontMenus')
            ->where('frontMenus', fn ($menus) => collect($menus)->contains('id', $this->pageMenu->id)));
});

test('a menu link saves only the menu and the front follows the menu url and target', function () {
    actingAsUserWithPermissions(['banner.item.manage']);

    $this->post(route('admin.banner.item.store'), bannerLinkPayload([
        'link_type' => 'menu', 'front_menu_info_id' => $this->pageMenu->id, 'url' => 'https://ignored.example', 'link_target' => '_blank',
    ]))->assertSessionHasNoErrors();

    $item = BannerItemInfo::query()->latest('id')->first();

    expect($item->link_type)->toBe('menu')
        ->and($item->front_menu_info_id)->toBe($this->pageMenu->id)
        ->and($item->url)->toBeNull()
        ->and($item->link_target)->toBeNull();

    expect(bannerFrontLink($item->id)['url'])->toContain("/th/page/item/{$this->pageMenu->target_page_item_id}");

    // เมนูถูกซ่อนภายหลัง = แสดงแบบไม่มีลิงก์ และไม่นับคลิก
    $this->pageMenu->update(['status' => 'N']);
    FrontCache::forgetAll();
    request()->attributes->remove('front.menu.th'); // FrontMenuResolver จำเมนูไว้ใน request ปัจจุบัน — จำลอง request ใหม่
    expect(bannerFrontLink($item->id)['url'])->toBeNull();
});

test('a custom link keeps url and target; the none type clears every link field', function () {
    actingAsUserWithPermissions(['banner.item.manage']);

    $this->post(route('admin.banner.item.store'), bannerLinkPayload([
        'link_type' => 'custom', 'front_menu_info_id' => $this->pageMenu->id, 'url' => '/promo', 'link_target' => '_blank',
    ]))->assertSessionHasNoErrors();
    $item = BannerItemInfo::query()->latest('id')->first();

    expect($item->front_menu_info_id)->toBeNull()
        ->and(bannerFrontLink($item->id))->toBe(['url' => '/th/promo', 'link_target' => '_blank']);

    $this->put(route('admin.banner.item.update', $item->id), bannerLinkPayload(['link_type' => 'none', 'url' => '/promo']))
        ->assertSessionHasNoErrors();

    expect($item->fresh()->only(['link_type', 'front_menu_info_id', 'url', 'link_target']))
        ->toBe(['link_type' => 'none', 'front_menu_info_id' => null, 'url' => null, 'link_target' => null])
        ->and(bannerFrontLink($item->id)['url'])->toBeNull();
});

test('the selected link type requires its own field', function () {
    actingAsUserWithPermissions(['banner.item.manage']);
    $heading = FrontMenuInfo::create(['menu_type' => 'heading', 'status' => 'Y']);

    $this->post(route('admin.banner.item.store'), bannerLinkPayload(['link_type' => 'menu']))
        ->assertSessionHasErrors('front_menu_info_id');
    $this->post(route('admin.banner.item.store'), bannerLinkPayload(['link_type' => 'menu', 'front_menu_info_id' => $heading->id]))
        ->assertSessionHasErrors('front_menu_info_id');
    $this->post(route('admin.banner.item.store'), bannerLinkPayload(['link_type' => 'custom', 'url' => '']))
        ->assertSessionHasErrors('url');
    $this->post(route('admin.banner.item.store'), bannerLinkPayload(['link_type' => 'custom', 'url' => 'javascript:alert(1)']))
        ->assertSessionHasErrors('url');
    $this->post(route('admin.banner.item.store'), bannerLinkPayload(['link_type' => 'other']))
        ->assertSessionHasErrors('link_type');

    // ไม่มีลิงก์ = ไม่ตรวจ url ที่ค้างอยู่ในฟอร์ม
    $this->post(route('admin.banner.item.store'), bannerLinkPayload(['link_type' => 'none', 'url' => 'not a url']))
        ->assertSessionHasNoErrors();
});

test('a menu that was disabled later can still be saved again on the same banner and is shown as inactive', function () {
    actingAsUserWithPermissions(['banner.item.view', 'banner.item.manage']);
    $item = BannerItemInfo::create(['banner_category_info_id' => $this->category->id, 'intro_image_id' => $this->image->id,
        'link_type' => 'menu', 'front_menu_info_id' => $this->pageMenu->id, 'publish_date' => now(), 'status' => 'Y']);
    BannerItemDetail::create(['id' => $item->id, 'lang' => 'th', 'title' => 'ป้าย', 'status' => 'Y']);
    $this->pageMenu->update(['status' => 'N']);

    $this->get(route('admin.banner.item.edit', $item->id))
        ->assertInertia(fn (Assert $page) => $page->where('frontMenus', fn ($menus) => collect($menus)
            ->contains(fn ($m) => $m['id'] === $this->pageMenu->id && ($m['inactive'] ?? false) === true)));

    $this->put(route('admin.banner.item.update', $item->id), bannerLinkPayload(['link_type' => 'menu', 'front_menu_info_id' => $this->pageMenu->id]))
        ->assertSessionHasNoErrors();

    // แต่รายการอื่นเลือกเมนูที่ปิดใช้งานไม่ได้
    $this->post(route('admin.banner.item.store'), bannerLinkPayload(['link_type' => 'menu', 'front_menu_info_id' => $this->pageMenu->id]))
        ->assertSessionHasErrors('front_menu_info_id');
});

test('clicks are counted for menu links but not for banners without a link', function () {
    $menuBanner = BannerItemInfo::create(['link_type' => 'menu', 'front_menu_info_id' => $this->pageMenu->id, 'status' => 'Y']);
    $leftover = BannerItemInfo::create(['link_type' => 'none', 'url' => 'https://example.com', 'status' => 'Y']);

    $this->post(route('front.banner.click'), ['id' => $menuBanner->id, 'lang' => 'th'])->assertNoContent();
    $this->post(route('front.banner.click'), ['id' => $leftover->id, 'lang' => 'th'])->assertNoContent();

    expect(DB::table('banner_item_click')->pluck('banner_item_info_id')->all())->toBe([$menuBanner->id]);
});
