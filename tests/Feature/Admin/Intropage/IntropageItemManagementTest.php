<?php

use App\Models\IntropageItemDetail;
use App\Models\IntropageItemInfo;
use App\Models\LogBackAction;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

// CRUD ของโมดูล Intropage (index/add/store/edit/update/destroy + กติกาปุ่ม) — การจัดรูปแบบตัวอักษรอยู่ใน IntropageItemStyleTest

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function introCrudPayload(array $overrides = []): array
{
    return array_replace([
        'detail' => ['th' => ['title' => 'หน้าแรกทดสอบ', 'detail' => 'ยินดีต้อนรับ'], 'en' => ['title' => 'Intro', 'detail' => 'Welcome']],
        'detail_font_family' => 'Sarabun',
        'detail_font_size' => 24,
        'detail_color' => '#000000',
        'display_type' => 'youtubeurl',
        'display_size' => 'screen_100',
        'vdo_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'background_color' => '#ffffff',
        'show_button' => 'Y',
        'button_font_size' => 18,
        'button_font_family' => 'Sarabun',
        'buttons' => [['button_type' => 'home', 'button_display_type' => 'text', 'texts' => ['th' => 'เข้าสู่เว็บไซต์']]],
        'publish_date' => now()->subHour()->format('Y-m-d H:i:s'),
        'publish_down' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => 'Y',
    ], $overrides);
}

test('index and add pages require permissions', function () {
    actingAsUserWithPermissions([]);
    $this->get(route('admin.intropage.item.index'))->assertRedirect(route('admin.dashboard'));

    actingAsUserWithPermissions(['intropage.item.view']);
    $this->get(route('admin.intropage.item.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Intropage/Item/Index')->has('items.data'));
    $this->get(route('admin.intropage.item.add'))->assertRedirect(route('admin.intropage.item.index'));

    actingAsUserWithPermissions(['intropage.item.view', 'intropage.item.manage']);
    $this->get(route('admin.intropage.item.add'))->assertOk();
});

test('store creates the intropage and logs the action', function () {
    actingAsUserWithPermissions(['intropage.item.view', 'intropage.item.manage']);

    $this->post(route('admin.intropage.item.store'), introCrudPayload())->assertSessionHasNoErrors();

    $item = IntropageItemInfo::latest('id')->firstOrFail();
    expect(IntropageItemDetail::where('id', $item->id)->where('lang', 'th')->value('title'))->toBe('หน้าแรกทดสอบ')
        ->and(LogBackAction::where('module_code', 'intropage.item')->where('action_type', 'create')->where('ref_id', $item->id)->exists())->toBeTrue();
});

test('exactly one home button is required', function () {
    actingAsUserWithPermissions(['intropage.item.view', 'intropage.item.manage']);

    $this->post(route('admin.intropage.item.store'), introCrudPayload(['buttons' => [
        ['button_type' => 'other', 'button_display_type' => 'text', 'texts' => ['th' => 'อื่น ๆ'], 'url' => 'https://example.com'],
    ]]))->assertSessionHasErrors('buttons');

    $this->post(route('admin.intropage.item.store'), introCrudPayload(['buttons' => [
        ['button_type' => 'home', 'button_display_type' => 'text', 'texts' => ['th' => 'หน้าแรก']],
        ['button_type' => 'home', 'button_display_type' => 'text', 'texts' => ['th' => 'หน้าแรก 2']],
    ]]))->assertSessionHasErrors('buttons');
});

test('button links and colours are validated', function () {
    actingAsUserWithPermissions(['intropage.item.view', 'intropage.item.manage']);

    $this->post(route('admin.intropage.item.store'), introCrudPayload([
        'background_color' => 'red;background:url(x)',
        'buttons' => [
            ['button_type' => 'home', 'button_display_type' => 'text', 'texts' => ['th' => 'หน้าแรก']],
            ['button_type' => 'other', 'button_display_type' => 'text', 'texts' => ['th' => 'ลิงก์'], 'url' => 'javascript:alert(1)'],
        ],
    ]))->assertSessionHasErrors(['background_color', 'buttons.1.url']);
});

test('update saves changes and destroy soft deletes with logs', function () {
    actingAsUserWithPermissions(['intropage.item.view', 'intropage.item.manage', 'intropage.item.delete']);
    $this->post(route('admin.intropage.item.store'), introCrudPayload())->assertSessionHasNoErrors();
    $item = IntropageItemInfo::latest('id')->firstOrFail();

    $this->get(route('admin.intropage.item.edit', $item->id))->assertOk();

    $this->put(route('admin.intropage.item.update', $item->id), introCrudPayload([
        'detail' => ['th' => ['title' => 'แก้ไขแล้ว', 'detail' => ''], 'en' => ['title' => '', 'detail' => '']],
    ]))->assertRedirect(route('admin.intropage.item.edit', $item->id));

    expect(IntropageItemDetail::where('id', $item->id)->where('lang', 'th')->value('title'))->toBe('แก้ไขแล้ว');

    $this->delete(route('admin.intropage.item.destroy', $item->id))->assertRedirect(route('admin.intropage.item.index'));

    $this->assertSoftDeleted('intropage_item_info', ['id' => $item->id]);
    expect(LogBackAction::where('module_code', 'intropage.item')->where('ref_id', $item->id)->orderBy('id')->pluck('action_type')->all())
        ->toBe(['create', 'view', 'update', 'delete']);
});

test('edit of a missing intropage goes back to the list', function () {
    actingAsUserWithPermissions(['intropage.item.view']);

    $this->get(route('admin.intropage.item.edit', 999999))->assertRedirect(route('admin.intropage.item.index'));
});
