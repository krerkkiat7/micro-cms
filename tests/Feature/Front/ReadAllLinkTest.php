<?php

use App\Models\ArticleCategoryInfo;
use App\Models\FrontMenuInfo;
use App\Models\PageItemColumn;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetGridArticle;
use App\Support\Front\FrontUrl;
use App\Support\PageWidget\PageWidgetRegistry;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

// ลิงก์ของปุ่ม "อ่านทั้งหมด" ที่หน้าบ้าน (เมนู / กำหนดเอง) + การเติมภาษาให้ path ภายใน

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('withLang prefixes the current language only to internal paths without a language', function () {
    expect(FrontUrl::withLang('/news', 'en'))->toBe('/en/news')
        ->and(FrontUrl::withLang('/', 'th'))->toBe('/th')
        ->and(FrontUrl::withLang('/news?a=1#top', 'th'))->toBe('/th/news?a=1#top')
        ->and(FrontUrl::withLang('/th/news', 'en'))->toBe('/th/news')
        ->and(FrontUrl::withLang('/en', 'th'))->toBe('/en')
        ->and(FrontUrl::withLang('/file/get/abc.jpg', 'th'))->toBe('/file/get/abc.jpg')
        ->and(FrontUrl::withLang('https://example.com/x', 'th'))->toBe('https://example.com/x')
        ->and(FrontUrl::withLang('//cdn.example.com/x', 'th'))->toBe('//cdn.example.com/x')
        ->and(FrontUrl::withLang('#top', 'th'))->toBe('#top')
        ->and(FrontUrl::withLang('mailto:a@b.c', 'th'))->toBe('mailto:a@b.c')
        ->and(FrontUrl::withLang(null, 'th'))->toBeNull();
});

/**
 * หน้าเพจใหม่ 1 แถว 1 คอลัมน์ ที่มี Grid จาก article 1 ตัว (ตั้งค่าตาม $setting) — คืน id ของหน้า
 */
function pageWithReadAllGrid(array $setting): int
{
    $category = ArticleCategoryInfo::query()->where('status', 'Y')->firstOrFail();
    $page = PageItemInfo::create(['status' => 'Y']);
    PageItemDetail::create(['id' => $page->id, 'lang' => 'th', 'title' => 'ทดสอบ', 'status' => 'Y']);
    $row = PageItemRow::create(['page_item_info_id' => $page->id, 'status' => 'Y']);
    $column = PageItemColumn::create(['page_item_row_id' => $row->id, 'status' => 'Y']);
    $widget = PageItemWidget::create(['page_item_column_id' => $column->id, 'widget_type' => 'gridarticle', 'status' => 'Y', 'show_title' => 'N']);
    PageWidgetRegistry::find('gridarticle')->save($widget->id, array_replace(
        PageWidgetRegistry::find('gridarticle')->defaults(),
        ['article_category_info_id' => $category->id, 'show_read_all' => 'Y'],
        $setting,
    ), null);

    return $page->id;
}

function readAllSettingOf(int $pageId, string $lang = 'th'): array
{
    $setting = null;
    test()->get("/{$lang}/page/item/{$pageId}")->assertOk()->assertInertia(function (Assert $p) use (&$setting) {
        $setting = $p->toArray()['props']['page']['rows'][0]['columns'][0]['widgets'][0]['setting'];
    });

    return $setting;
}

test('a custom read-all link gets the page language when it is an internal path without one', function () {
    $pageId = pageWithReadAllGrid(['read_all_link_type' => 'custom', 'read_all_url' => '/news', 'read_all_link_target' => '_blank']);

    $setting = readAllSettingOf($pageId, 'en');

    expect($setting['read_all_url'])->toBe('/en/news')
        ->and($setting['read_all_link_target'])->toBe('_blank')
        ->and($setting)->not->toHaveKeys(['read_all_menu_id', 'read_all_link_type']);
});

test('a menu read-all link follows the menu url and target; hidden menus hide the button', function () {
    $pageMenu = FrontMenuInfo::query()->where('status', 'Y')->where('menu_type', 'page')->firstOrFail();
    $external = FrontMenuInfo::create(['menu_type' => 'external', 'url' => '/promo', 'link_target' => '_blank', 'status' => 'Y']);

    $pageId = pageWithReadAllGrid(['read_all_link_type' => 'menu', 'read_all_menu_id' => $pageMenu->id]);
    $setting = readAllSettingOf($pageId);

    expect($setting['read_all_url'])->toContain("/th/page/item/{$pageMenu->target_page_item_id}")
        ->and($setting['read_all_link_target'])->toBe($pageMenu->link_target === '_blank' ? '_blank' : '_self');

    PageItemWidgetGridArticle::query()->update(['read_all_menu_id' => $external->id]);
    \App\Support\Front\FrontCache::forgetAll(); // update ผ่าน query builder ไม่ผ่าน model event จึงไม่ล้าง cache หน้าบ้านเอง
    expect(readAllSettingOf($pageId)['read_all_url'])->toBe('/th/promo')
        ->and(readAllSettingOf($pageId)['read_all_link_target'])->toBe('_blank');

    $external->update(['status' => 'N']);
    expect(readAllSettingOf($pageId)['read_all_url'])->toBeNull();
});
