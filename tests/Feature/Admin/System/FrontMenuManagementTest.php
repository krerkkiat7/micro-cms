<?php

use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;
use App\Models\LogBackAction;
use App\Support\FrontMenuType;
use Database\Seeders\DatabaseSeeder;

// CRUD ของโมดูลจัดการเมนูหน้าบ้าน (store/update/destroy/status/reorder/pickers) — ชื่อเมนูใน tree อยู่ใน FrontMenuNameTest

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function menuPayload(array $overrides = []): array
{
    return array_replace([
        'parent_id' => null,
        'menu_type' => FrontMenuType::EXTERNAL,
        'target_article_category_id' => null,
        'target_article_item_id' => null,
        'target_page_item_id' => null,
        'url' => 'https://example.com',
        'link_target' => '_blank',
        'is_home' => 'N',
        'show_header_image' => 'N',
        'header_image_id' => null,
        'header_image_aspect_ratio' => 'natural',
        'header_image_fit' => 'cover',
        'header_image_background' => 'transparent',
        'show_title' => 'Y',
        'title_font_size' => 32,
        'title_font_family' => 'Sarabun',
        'title_color' => '#000000',
        'title_bold' => 'Y',
        'show_subtitle' => 'N',
        'subtitle_font_size' => 18,
        'subtitle_font_family' => 'Sarabun',
        'subtitle_color' => '#000000',
        'subtitle_bold' => 'N',
        'header_content_align' => 'center',
        'use_container' => 'Y',
        'show_breadcrumb' => 'Y',
        'status' => 'Y',
        'detail' => ['th' => ['name' => 'ลิงก์ทดสอบ'], 'en' => ['name' => 'Test link']],
    ], $overrides);
}

function lastMenu(): FrontMenuInfo
{
    return FrontMenuInfo::latest('id')->firstOrFail();
}

test('menu pages and actions require permissions', function () {
    actingAsUserWithPermissions([]);
    $this->get(route('admin.system.menu.index'))->assertRedirect(route('admin.dashboard'));

    actingAsUserWithPermissions(['system.menu.view']);
    $this->post(route('admin.system.menu.store'), menuPayload());
    expect(FrontMenuDetail::where('name', 'ลิงก์ทดสอบ')->exists())->toBeFalse();
});

test('store creates a menu with details and logs it; external links are validated', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage']);

    $this->post(route('admin.system.menu.store'), menuPayload(['url' => 'www.example.com']))->assertSessionHasErrors('url');
    $this->post(route('admin.system.menu.store'), menuPayload())->assertSessionHasNoErrors();

    $menu = lastMenu();
    expect($menu->url)->toBe('https://example.com')
        ->and(FrontMenuDetail::where('id', $menu->id)->where('lang', 'th')->value('name'))->toBe('ลิงก์ทดสอบ')
        ->and(LogBackAction::where('module_code', 'system.menu')->where('action_type', 'create')->where('ref_id', $menu->id)->exists())->toBeTrue();
});

test('only heading menus can be parents', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage']);
    $link = FrontMenuInfo::where('menu_type', '!=', FrontMenuType::HEADING)->firstOrFail();

    $this->post(route('admin.system.menu.store'), menuPayload(['parent_id' => $link->id]))->assertSessionHasErrors('parent_id');
});

test('update changes the menu and making another menu home moves the home flag', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage']);
    $oldHome = FrontMenuInfo::where('is_home', 'Y')->firstOrFail();
    $this->post(route('admin.system.menu.store'), menuPayload())->assertSessionHasNoErrors();
    $menu = lastMenu();

    $this->put(route('admin.system.menu.update', $menu->id), menuPayload([
        'url' => '/th/contactus', 'link_target' => '_self', 'is_home' => 'Y',
        'detail' => ['th' => ['name' => 'แก้ไขแล้ว'], 'en' => ['name' => 'Edited']],
    ]))->assertSessionHasNoErrors();

    expect($menu->fresh()->is_home)->toBe('Y')
        ->and($oldHome->fresh()->is_home)->toBe('N')
        ->and(FrontMenuDetail::where('id', $menu->id)->where('lang', 'th')->value('name'))->toBe('แก้ไขแล้ว');

    // ยกเลิก is_home ของเมนูหน้าแรกเองไม่ได้ (ต้องตั้งเมนูอื่นแทน)
    $this->put(route('admin.system.menu.update', $menu->id), menuPayload(['url' => '/th/contactus', 'is_home' => 'N']))
        ->assertSessionHasErrors('is_home');
});

test('destroy refuses the home menu and menus with children, and soft deletes others', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage', 'system.menu.delete']);
    $home = FrontMenuInfo::where('is_home', 'Y')->firstOrFail();

    $this->delete(route('admin.system.menu.destroy', $home->id))->assertSessionHasErrors('menu');

    $heading = FrontMenuInfo::where('menu_type', FrontMenuType::HEADING)
        ->whereIn('id', FrontMenuInfo::whereNotNull('parent_id')->select('parent_id'))
        ->first();
    if ($heading) {
        $this->delete(route('admin.system.menu.destroy', $heading->id))->assertSessionHasErrors('menu');
    }

    $this->post(route('admin.system.menu.store'), menuPayload())->assertSessionHasNoErrors();
    $menu = lastMenu();
    $this->delete(route('admin.system.menu.destroy', $menu->id))->assertSessionHasNoErrors();
    $this->assertSoftDeleted('front_menu_info', ['id' => $menu->id]);
});

test('hiding a heading hides its children too', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage']);
    $this->post(route('admin.system.menu.store'), menuPayload(['menu_type' => FrontMenuType::HEADING, 'url' => null, 'link_target' => '_self']))
        ->assertSessionHasNoErrors();
    $heading = lastMenu();
    $this->post(route('admin.system.menu.store'), menuPayload(['parent_id' => $heading->id]))->assertSessionHasNoErrors();
    $child = lastMenu();

    $this->put(route('admin.system.menu.status', $heading->id))->assertSessionHasNoErrors();

    expect($heading->fresh()->status)->toBe('N')->and($child->fresh()->status)->toBe('N');
});

test('reorder rejects cycles and parents that are not headings', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage']);
    $this->post(route('admin.system.menu.store'), menuPayload(['menu_type' => FrontMenuType::HEADING, 'url' => null, 'link_target' => '_self']))->assertSessionHasNoErrors();
    $a = lastMenu();
    $this->post(route('admin.system.menu.store'), menuPayload(['menu_type' => FrontMenuType::HEADING, 'url' => null, 'link_target' => '_self', 'parent_id' => $a->id]))->assertSessionHasNoErrors();
    $b = lastMenu();
    $this->post(route('admin.system.menu.store'), menuPayload())->assertSessionHasNoErrors();
    $link = lastMenu();

    // a อยู่ใต้ b ที่อยู่ใต้ a = วนลูป
    $this->put(route('admin.system.menu.reorder'), ['order' => [
        ['id' => $a->id, 'parent_id' => $b->id, 'sort_order' => 1],
        ['id' => $b->id, 'parent_id' => $a->id, 'sort_order' => 1],
    ]])->assertSessionHasErrors('order');

    $this->put(route('admin.system.menu.reorder'), ['order' => [
        ['id' => $b->id, 'parent_id' => $link->id, 'sort_order' => 1],
    ]])->assertSessionHasErrors('order');

    expect($a->fresh()->parent_id)->toBeNull()->and($b->fresh()->parent_id)->toBe($a->id);
});

test('article and page pickers return json for users who can view menus', function () {
    actingAsUserWithPermissions(['system.menu.view', 'system.menu.manage']);

    $this->getJson(route('admin.system.menu.pick.articles'))->assertOk();
    $this->getJson(route('admin.system.menu.pick.pages'))->assertOk();
});
