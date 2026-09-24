<?php

use App\Models\IntropageItemInfo;
use App\Support\Front\FrontCache;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * การจัดรูปแบบตัวอักษรของ Intropage — ข้อความต้อนรับ (ฟอนต์/ขนาด/สี) และปุ่ม (ฟอนต์/ขนาด)
 */
beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function intropagePayload(array $overrides = []): array
{
    return array_replace([
        'detail' => ['th' => ['title' => 'ทดสอบ', 'detail' => 'ยินดีต้อนรับ'], 'en' => ['title' => '', 'detail' => '']],
        'detail_font_family' => 'Prompt',
        'detail_font_size' => 24,
        'detail_color' => '#FF0000',
        'display_type' => 'youtubeurl',
        'display_size' => 'screen_100',
        'vdo_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'background_color' => '#ffffff',
        'show_button' => 'Y',
        'button_font_size' => 20,
        'button_font_family' => 'Kanit',
        'buttons' => [['button_type' => 'home', 'button_display_type' => 'text', 'texts' => ['th' => 'เข้าสู่เว็บไซต์']]],
        'publish_date' => now()->subHour()->format('Y-m-d H:i:s'),
        'publish_down' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => 'Y',
    ], $overrides);
}

test('intropage stores welcome text and button styles', function () {
    actingAsUserWithPermissions(['intropage.item.view', 'intropage.item.manage']);

    $this->post(route('admin.intropage.item.store'), intropagePayload())->assertSessionHasNoErrors();

    $item = IntropageItemInfo::latest('id')->firstOrFail();
    expect($item->detail_font_family)->toBe('Prompt')
        ->and((int) $item->detail_font_size)->toBe(24)
        ->and($item->detail_color)->toBe('#FF0000')
        ->and((int) $item->button_font_size)->toBe(20)
        ->and($item->button_font_family)->toBe('Kanit');

    $this->get(route('admin.intropage.item.edit', $item->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('item.detail_font_family', 'Prompt')
            ->where('item.button_font_size', 20)
            ->has('fonts'));
});

test('intropage style fields are validated', function () {
    actingAsUserWithPermissions(['intropage.item.view', 'intropage.item.manage']);

    $this->post(route('admin.intropage.item.store'), intropagePayload([
        'detail_font_family' => 'Comic Sans',
        'detail_font_size' => 500,
        'detail_color' => 'red',
        'button_font_family' => 'x',
    ]))->assertSessionHasErrors(['detail_font_family', 'detail_font_size', 'detail_color', 'button_font_family']);
});

test('front intropage receives the text styles', function () {
    IntropageItemInfo::query()->update(['detail_font_family' => 'Prompt', 'detail_font_size' => 22, 'button_font_family' => 'Kanit']);
    FrontCache::forgetAll();

    $this->get('/th')->assertInertia(fn (Assert $page) => $page
        ->component('Front/Intropage/Index')
        ->where('intro.detail_style.font_family', 'Prompt')
        ->where('intro.detail_style.font_size', 22)
        ->where('intro.button_style.font_family', 'Kanit'));
});
