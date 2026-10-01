<?php

use App\Models\FileInfo;
use App\Models\FrontMenuInfo;
use App\Models\PopupItemInfo;
use App\Models\PopupItemPart;
use App\Models\PopupItemPartDetail;
use App\Models\SysSetting;
use App\Support\Front\FrontCache;
use App\Support\Front\FrontMenuResolver;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * popup ที่หน้าบ้าน (docs/PRD-popup.md §3) — prop `popups` ของหน้าที่ใช้ FrontLayout กรองตามช่วงเผยแพร่/สถานะ/เมนูของหน้า
 */
beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // popup ตัวอย่าง (PopupSeeder) ไม่เกี่ยวกับเทสนี้ — แต่ละเทสสร้าง popup ของตัวเอง
    PopupItemInfo::query()->delete();
    FrontCache::forgetAll();

    // หน้าเพจตัวอย่างที่มีเมนูชี้มา — ใช้เป็นหน้าทดสอบหลัก
    $this->pageMenu = FrontMenuInfo::query()->where('menu_type', FrontMenuType::PAGE)->where('status', 'Y')->orderBy('id')->firstOrFail();
    $this->pageId = $this->pageMenu->target_page_item_id;
    $this->menuId = FrontMenuResolver::findFor('th', FrontMenuType::PAGE, $this->pageId)['id'];
    $this->pageUrl = "/th/page/item/{$this->pageId}";
});

/**
 * @param  array<string, mixed>  $attributes
 * @param  list<array<string, mixed>>|null  $parts
 */
function makePopup(array $attributes = [], ?array $parts = null, array $menuIds = []): PopupItemInfo
{
    $popup = PopupItemInfo::create(array_replace([
        'name' => 'Popup',
        'display_type' => 'modal',
        'menu_mode' => 'all',
        'publish_date' => now()->subDay(),
        'publish_down' => null,
        'status' => 'Y',
    ], $attributes));

    foreach ($parts ?? [['part_type' => 'text', 'text' => '<p>สวัสดี</p>']] as $index => $part) {
        $model = PopupItemPart::create([
            'popup_item_info_id' => $popup->id,
            'part_type' => $part['part_type'],
            'image_id' => $part['image_id'] ?? null,
            'url' => $part['url'] ?? null,
            'status' => $part['status'] ?? 'Y',
            'sort_order' => $index,
        ]);

        if (isset($part['text'])) {
            PopupItemPartDetail::create(['id' => $model->id, 'lang' => 'th', 'detail' => $part['text']]);
        }
    }

    $popup->menus()->sync($menuIds);
    FrontCache::forgetAll();

    return $popup;
}

test('a published popup set to all pages is sent with its settings and sanitized parts', function () {
    $image = FileInfo::create(['name' => 'p.jpg', 'hash_name' => 'p-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);
    $popup = makePopup(['display_type' => 'modal', 'slide_interval' => 7], [
        ['part_type' => 'image_text', 'image_id' => $image->id, 'text' => '<p>hi<script>alert(1)</script></p>', 'url' => '/page/item/1'],
        ['part_type' => 'image', 'image_id' => $image->id, 'url' => 'javascript:alert(1)'],
    ]);

    $this->get($this->pageUrl)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Front/Page/Item')
            ->has('popups', 1)
            ->where('popups.0.id', $popup->id)
            ->where('popups.0.display_type', 'modal')
            ->where('popups.0.slide_interval', 7)
            ->has('popups.0.parts', 2)
            ->where('popups.0.parts.0.html', fn ($html) => ! str_contains($html, 'script') && str_contains($html, 'hi'))
            ->where('popups.0.parts.0.url', '/th/page/item/1')
            ->where('popups.0.parts.0.image.url', fn ($url) => str_contains($url, 'p-hash.jpg'))
            ->where('popups.0.parts.1.url', null)
            ->missing('popups.0.menu_ids')
        );
});

test('a floating popup sends images only — text parts and text of image parts are dropped', function () {
    $image = FileInfo::create(['name' => 'f.jpg', 'hash_name' => 'f-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y']);
    makePopup(['display_type' => 'floating'], [
        ['part_type' => 'text', 'text' => '<p>text only</p>'],
        ['part_type' => 'image_text', 'image_id' => $image->id, 'text' => '<p>caption</p>'],
    ]);

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page
        ->where('popups.0.display_type', 'floating')
        ->has('popups.0.parts', 1)
        ->where('popups.0.parts.0.html', null)
        ->where('popups.0.parts.0.image.url', fn ($url) => str_contains($url, 'f-hash.jpg')));
});

test('a popup for selected menus shows only on pages of those menus', function () {
    $other = FrontMenuInfo::query()->where('menu_type', FrontMenuType::ARTICLE_CATEGORY)->firstOrFail();
    makePopup(['menu_mode' => 'selected'], null, [$this->menuId]);
    makePopup(['menu_mode' => 'selected', 'name' => 'other'], null, [$other->id]);

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page->has('popups', 1));

    PopupItemInfo::query()->where('name', 'Popup')->first()->menus()->sync([$other->id]);
    FrontCache::forgetAll();

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page->has('popups', 0));
});

test('popups set to none, disabled, outside the publish window or with only hidden parts are not shown', function () {
    makePopup(['menu_mode' => 'none']);
    makePopup(['status' => 'N']);
    makePopup(['publish_date' => now()->addDay()]);
    makePopup(['publish_date' => now()->subDays(3), 'publish_down' => now()->subDay()]);
    makePopup([], [['part_type' => 'text', 'text' => '<p>hidden</p>', 'status' => 'N']]);

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page->has('popups', 0));
});

test('hidden parts are left out of a shown popup', function () {
    makePopup([], [
        ['part_type' => 'text', 'text' => '<p>shown</p>'],
        ['part_type' => 'text', 'text' => '<p>hidden</p>', 'status' => 'N'],
    ]);

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page
        ->has('popups.0.parts', 1)
        ->where('popups.0.parts.0.html', fn ($html) => str_contains($html, 'shown')));
});

test('popups follow the display order setting', function () {
    $older = makePopup(['name' => 'older', 'publish_date' => now()->subDays(2), 'sort_order' => 9]);
    $newer = makePopup(['name' => 'newer', 'publish_date' => now()->subDay(), 'sort_order' => 1]);

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page
        ->where('popups.0.id', $newer->id)
        ->where('popups.1.id', $older->id));

    SysSetting::create(['group' => 'popup', 'name' => 'display_order', 'value' => 'sort_desc']);
    Setting::forget('popup');

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page
        ->where('popups.0.id', $older->id)
        ->where('popups.1.id', $newer->id));
});

test('the intropage does not receive popups', function () {
    makePopup();

    $this->get('/th')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Front/Intropage/Index')->missing('popups'));
});

test('saving a popup in the back office refreshes the front cache', function () {
    $popup = makePopup();
    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page->has('popups', 1));

    $popup->update(['status' => 'N']);

    $this->get($this->pageUrl)->assertInertia(fn (Assert $page) => $page->has('popups', 0));
});
