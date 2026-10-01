<?php

use App\Models\BannerCategoryDetail;
use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\PageItemColumn;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetSlideshowBanner;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างหมวดหมู่ banner ตัวอย่าง (Highlight พร้อมป้ายโฆษณา) — เทสใช้หมวดหมู่ว่างของตัวเอง
    $this->seed(DatabaseSeeder::class);
    $this->category = BannerCategoryInfo::create(['status' => 'Y']);
    BannerCategoryDetail::create(['id' => $this->category->id, 'lang' => 'th', 'title' => 'หมวดของเทส', 'status' => 'Y']);
    $this->image = FileInfo::create([
        'name' => 'banner.jpg', 'hash_name' => 'banner-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $this->page = PageItemInfo::create(['status' => 'Y']);
    PageItemDetail::create(['id' => $this->page->id, 'lang' => 'th', 'title' => 'หน้าเปล่า', 'status' => 'Y']);
});

/**
 * @return array<string, mixed>
 */
function slideshowSetting(int $categoryId, array $overrides = []): array
{
    return array_replace([
        'banner_category_info_id' => $categoryId,
        'sort_by' => 'publish_desc',
        'show_arrows' => 'Y',
        'show_dots' => 'Y',
        'autoplay' => 'Y',
        'autoplay_interval' => 5,
        'transition_speed' => 500,
        'transition_effect' => 'slide',
        'aspect_ratio' => '16:9',
        'is_clickable' => 'Y',
        'link_target' => '_self',
        'show_title' => 'Y',
        'show_intro_text' => 'N',
        'text_align' => 'center',
        'text_width' => 'container',
        'max_items' => 0,
        'title_font_size' => 20,
        'title_font_family' => 'Sarabun',
        'title_color' => '#FFFFFF',
        'title_bold' => 'Y',
        'intro_text_font_size' => 16,
        'intro_text_font_family' => 'Sarabun',
        'intro_text_color' => '#FFFFFF',
        'intro_text_bold' => 'N',
    ], $overrides);
}

/**
 * payload หน้าเปล่า 1 แถว 1 คอลัมน์ ที่มี widget ตามที่ส่งมา — ใช้ helper layoutRow/layoutColumn/layoutWidget ของ PageItemLayoutTest
 *
 * @param  list<array<string, mixed>>  $widgets
 * @return array<string, mixed>
 */
function slideshowPayload(array $widgets): array
{
    return ['rows' => [layoutRow([layoutColumn($widgets)])]];
}

function slideshowWidget(int $categoryId, array $settingOverrides = [], array $widgetOverrides = []): array
{
    return layoutWidget(['widget_type' => 'slideshowbanner', 'setting' => slideshowSetting($categoryId, $settingOverrides)] + $widgetOverrides);
}

/** widget ที่สร้างล่าสุดของประเภทนั้น (ในเทสคือตัวที่เพิ่งบันทึกลงหน้าเปล่า ไม่ใช่ตัวอย่างของ PageSeeder) */
function newestWidget(string $type): ?PageItemWidget
{
    return PageItemWidget::where('widget_type', $type)->orderByDesc('id')->first();
}

function bannerItem(int $categoryId, int $imageId, array $overrides = [], string $title = 'ป้าย'): BannerItemInfo
{
    $item = BannerItemInfo::create($overrides + [
        'banner_category_info_id' => $categoryId,
        'intro_image_id' => $imageId,
        'publish_date' => now()->subDay(),
        'status' => 'Y',
    ]);
    BannerItemDetail::create(['id' => $item->id, 'lang' => 'th', 'title' => $title, 'intro_text' => 'เกริ่นนำ '.$title, 'status' => 'Y']);

    return $item;
}

// ---------------------------------------------------------------- save

test('saving a slideshowbanner widget creates its settings row with the same id as the widget', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slideshowPayload([
        slideshowWidget($this->category->id, ['sort_by' => 'order_asc', 'autoplay_interval' => 8, 'transition_effect' => 'fade', 'aspect_ratio' => '21:9', 'show_intro_text' => 'Y', 'text_align' => 'top right', 'text_width' => 'full', 'link_target' => '_blank']),
    ]))->assertSessionHasNoErrors();

    $widget = newestWidget('slideshowbanner');
    $setting = PageItemWidgetSlideshowBanner::findOrFail($widget->id);

    expect($setting->banner_category_info_id)->toBe($this->category->id)
        ->and($setting->sort_by)->toBe('order_asc')
        ->and($setting->autoplay_interval)->toBe(8)
        ->and($setting->transition_effect)->toBe('fade')
        ->and($setting->aspect_ratio)->toBe('21:9')
        ->and($setting->show_intro_text)->toBe('Y')
        ->and($setting->text_align)->toBe('top right')
        ->and($setting->text_width)->toBe('full')
        ->and($setting->link_target)->toBe('_blank')
        ->and($setting->created_by)->toBe($me->id);
});

test('saving again updates the same settings row instead of creating another', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);
    $before = PageItemWidgetSlideshowBanner::count();

    $this->put($url, slideshowPayload([slideshowWidget($this->category->id)]))->assertSessionHasNoErrors();
    $widget = newestWidget('slideshowbanner');

    $this->put($url, slideshowPayload([slideshowWidget($this->category->id, ['show_dots' => 'N', 'autoplay' => 'N'], ['id' => $widget->id])]))
        ->assertSessionHasNoErrors();

    expect(PageItemWidgetSlideshowBanner::count())->toBe($before + 1)
        ->and(PageItemWidgetSlideshowBanner::find($widget->id))
        ->show_dots->toBe('N')
        ->autoplay->toBe('N');
});

test('the layout page serves the saved slideshow setting and the banner category options', function () {
    actingAsUserWithPermissions(['page.item.view', 'page.item.manage']);
    $this->put(route('admin.page.item.layout.update', $this->page->id), slideshowPayload([
        slideshowWidget($this->category->id, ['sort_by' => 'order_desc', 'autoplay_interval' => 12]),
    ]))->assertSessionHasNoErrors();

    $this->get(route('admin.page.item.layout', $this->page->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.columns.0.widgets.0.widget_type', 'slideshowbanner')
            ->where('rows.0.columns.0.widgets.0.setting.banner_category_info_id', $this->category->id)
            ->where('rows.0.columns.0.widgets.0.setting.sort_by', 'order_desc')
            ->where('rows.0.columns.0.widgets.0.setting.autoplay_interval', 12)
            ->has('widgetOptions.banner_categories', 2)
            ->where('widgetOptions.banner_categories.0.id', fn ($id) => is_int($id))
        );
});

test('a legacy placeholder widget still saves without a settings row', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slideshowPayload([layoutWidget()]))
        ->assertSessionHasNoErrors();

    $widget = newestWidget('placeholder');
    expect($widget)->not->toBeNull()
        ->and(PageItemWidgetSlideshowBanner::where('id', $widget->id)->exists())->toBeFalse();
});

// ---------------------------------------------------------------- validation

test('slideshowbanner requires an existing, enabled banner category', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slideshowPayload([slideshowWidget($this->category->id, ['banner_category_info_id' => null])]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.banner_category_info_id');

    $this->put($url, slideshowPayload([slideshowWidget(999999)]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.banner_category_info_id');

    $this->category->update(['status' => 'N']);
    $this->put($url, slideshowPayload([slideshowWidget($this->category->id)]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.banner_category_info_id');

    expect(PageItemRow::where('page_item_info_id', $this->page->id)->count())->toBe(0); // บันทึกไม่สำเร็จ ไม่มีอะไรถูกสร้าง
});

test('slideshowbanner rejects out-of-range or unknown setting values', function (string $field, mixed $value) {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slideshowPayload([slideshowWidget($this->category->id, [$field => $value])]))
        ->assertSessionHasErrors("rows.0.columns.0.widgets.0.setting.{$field}");
})->with([
    'sort' => ['sort_by', 'random'],
    'effect' => ['transition_effect', 'flip'],
    'ratio' => ['aspect_ratio', '3:1'],
    'target' => ['link_target', '_top'],
    'align' => ['text_align', 'justify'],
    'align middle' => ['text_align', 'middle left'],
    'title bold' => ['title_bold', 'yes'],
    'intro bold' => ['intro_text_bold', '1'],
    'width' => ['text_width', 'narrow'],
    'flag' => ['show_arrows', 'yes'],
    'interval low' => ['autoplay_interval', 0],
    'interval high' => ['autoplay_interval', 61],
    'speed low' => ['transition_speed', 50],
    'speed high' => ['transition_speed', 3001],
    'max items negative' => ['max_items', -1],
    'max items too big' => ['max_items', 1001],
    'max items not a number' => ['max_items', 'abc'],
    'title size low' => ['title_font_size', 7],
    'title size high' => ['title_font_size', 121],
    'title font unknown' => ['title_font_family', 'Comic Sans'],
    'title color not hex' => ['title_color', 'white'],
    'intro size' => ['intro_text_font_size', 500],
    'intro font unknown' => ['intro_text_font_family', 'Comic Sans'],
    'intro color transparent' => ['intro_text_color', 'transparent'],
]);

test('max_items and the overlay text style are saved; an empty max_items means show all (0)', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slideshowPayload([slideshowWidget($this->category->id, [
        'max_items' => 5, 'title_font_size' => 32, 'title_font_family' => 'Kanit', 'title_color' => '#ff0000',
        'intro_text_font_size' => 14, 'intro_text_font_family' => 'Mitr', 'intro_text_color' => '#000000',
    ])]))->assertSessionHasNoErrors();

    $widget = newestWidget('slideshowbanner');
    $row = PageItemWidgetSlideshowBanner::findOrFail($widget->id);
    expect($row->max_items)->toBe(5)
        ->and($row->title_font_size)->toBe(32)
        ->and($row->title_font_family)->toBe('Kanit')
        ->and($row->title_color)->toBe('#ff0000')
        ->and($row->intro_text_font_size)->toBe(14)
        ->and($row->intro_text_font_family)->toBe('Mitr')
        ->and($row->intro_text_color)->toBe('#000000');

    // ไม่กรอก (null) = 0 = แสดงทั้งหมด
    $this->put($url, slideshowPayload([slideshowWidget($this->category->id, ['max_items' => null], ['id' => $widget->id])]))->assertSessionHasNoErrors();
    expect(PageItemWidgetSlideshowBanner::find($widget->id)->max_items)->toBe(0);
});

test('the overlay text style fields are required in the payload (the screen always sends them)', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $setting = slideshowSetting($this->category->id);
    foreach (['max_items', 'title_font_size', 'title_font_family', 'title_color', 'intro_text_font_size', 'intro_text_font_family', 'intro_text_color'] as $key) {
        unset($setting[$key]);
    }
    $this->put(route('admin.page.item.layout.update', $this->page->id), slideshowPayload([layoutWidget(['widget_type' => 'slideshowbanner', 'setting' => $setting])]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.title_color');
});

test('the type of a saved widget cannot be changed', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slideshowPayload([slideshowWidget($this->category->id)]))->assertSessionHasNoErrors();
    $widget = newestWidget('slideshowbanner');

    $this->put($url, slideshowPayload([layoutWidget(['id' => $widget->id])])) // ส่งเป็น placeholder
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.widget_type');

    expect($widget->fresh()->widget_type)->toBe('slideshowbanner');
});

test('an unknown widget type is rejected', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slideshowPayload([layoutWidget(['widget_type' => 'not_a_real_type'])]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.widget_type');
});

// ---------------------------------------------------------------- delete / move

test('removing a widget also soft deletes its settings row', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slideshowPayload([slideshowWidget($this->category->id)]))->assertSessionHasNoErrors();
    $widget = newestWidget('slideshowbanner');

    $this->put($url, slideshowPayload([]))->assertSessionHasNoErrors();

    expect(PageItemWidget::find($widget->id))->toBeNull()
        ->and(PageItemWidgetSlideshowBanner::find($widget->id))->toBeNull()
        ->and(PageItemWidgetSlideshowBanner::withTrashed()->find($widget->id))->not->toBeNull();
});

test('moving a widget to another column keeps its settings', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, ['rows' => [layoutRow([
        layoutColumn([slideshowWidget($this->category->id, ['autoplay_interval' => 9])], ['column_size' => 6]),
        layoutColumn([], ['column_size' => 6]),
    ])]])->assertSessionHasNoErrors();

    $widget = newestWidget('slideshowbanner');
    $columns = PageItemColumn::where('page_item_row_id', PageItemRow::where('page_item_info_id', $this->page->id)->value('id'))->orderBy('sort_order')->get();

    $this->put($url, ['rows' => [layoutRow([
        layoutColumn([], ['id' => $columns[0]->id, 'column_size' => 6]),
        layoutColumn([slideshowWidget($this->category->id, ['autoplay_interval' => 9], ['id' => $widget->id])], ['id' => $columns[1]->id, 'column_size' => 6]),
    ], ['id' => $columns[0]->page_item_row_id])]])->assertSessionHasNoErrors();

    expect($widget->fresh()->page_item_column_id)->toBe($columns[1]->id)
        ->and(PageItemWidgetSlideshowBanner::find($widget->id)->autoplay_interval)->toBe(9);
});

// ---------------------------------------------------------------- preview

function previewUrl(array $setting): string
{
    return route('admin.page.item.widget.preview', ['widget_type' => 'slideshowbanner', 'setting' => $setting]);
}

test('preview requires page.item.view', function () {
    actingAsUserWithPermissions([]);

    $this->getJson(previewUrl(['banner_category_info_id' => $this->category->id, 'sort_by' => 'publish_desc']))->assertForbidden();
});

test('preview validates the widget type and the settings that drive the data', function () {
    actingAsUserWithPermissions(['page.item.view']);

    $this->getJson(route('admin.page.item.widget.preview', ['widget_type' => 'nope']))->assertStatus(422);
    $this->getJson(previewUrl(['sort_by' => 'publish_desc']))->assertStatus(422)->assertJsonPath('items', []);
    $this->getJson(previewUrl(['banner_category_info_id' => $this->category->id, 'sort_by' => 'random']))->assertStatus(422);
});

test('preview returns only published banners with an image, without link urls', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    bannerItem($cid, $this->image->id, ['link_type' => 'custom', 'url' => 'https://example.com/secret'], 'แสดง');
    bannerItem($cid, $this->image->id, ['url' => null], 'ไม่มีลิงก์');
    bannerItem($cid, $this->image->id, ['status' => 'N'], 'ปิดอยู่');
    bannerItem($cid, $this->image->id, ['publish_date' => now()->addDay()], 'ยังไม่ถึงเวลา');
    bannerItem($cid, $this->image->id, ['publish_down' => now()->subHour()], 'หมดเวลาแล้ว');
    bannerItem($cid, $this->image->id, ['intro_image_id' => null], 'ไม่มีรูป');
    bannerItem($cid, $this->image->id, ['banner_category_info_id' => BannerCategoryInfo::query()->where('id', '!=', $cid)->value('id')], 'หมวดอื่น');
    bannerItem($cid, $this->image->id, [], 'ถูกลบ')->delete();

    $response = $this->getJson(previewUrl(['banner_category_info_id' => $cid, 'sort_by' => 'publish_desc']))->assertOk();

    expect(collect($response->json('items'))->pluck('title')->sort()->values()->all())->toBe(['แสดง', 'ไม่มีลิงก์'])
        ->and(collect($response->json('items'))->pluck('has_link', 'title')->all())->toBe(['แสดง' => true, 'ไม่มีลิงก์' => false])
        ->and($response->json('items.0.image'))->toBe('banner-hash.jpg')
        ->and($response->getContent())->not->toContain('example.com');
});

test('preview sorts by publish date and by order in both directions', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    bannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(3), 'sort_order' => 2], 'เก่า');
    bannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(1), 'sort_order' => 3], 'ใหม่');
    bannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(2), 'sort_order' => 1], 'กลาง');

    $titles = fn (string $sort) => collect($this->getJson(previewUrl(['banner_category_info_id' => $cid, 'sort_by' => $sort]))->json('items'))->pluck('title')->all();

    expect($titles('publish_desc'))->toBe(['ใหม่', 'กลาง', 'เก่า'])
        ->and($titles('publish_asc'))->toBe(['เก่า', 'กลาง', 'ใหม่'])
        ->and($titles('order_asc'))->toBe(['กลาง', 'เก่า', 'ใหม่'])
        ->and($titles('order_desc'))->toBe(['ใหม่', 'เก่า', 'กลาง']);
});

test('preview honours max_items (0 or empty = all, capped at the preview limit of 10)', function () {
    actingAsUserWithPermissions(['page.item.view']);
    foreach (range(1, 12) as $i) {
        bannerItem($this->category->id, $this->image->id, [], "ป้าย {$i}");
    }
    $count = fn (array $extra) => count($this->getJson(previewUrl(['banner_category_info_id' => $this->category->id, 'sort_by' => 'publish_desc'] + $extra))->assertOk()->json('items'));

    expect($count(['max_items' => 3]))->toBe(3)
        ->and($count(['max_items' => 0]))->toBe(10)
        ->and($count([]))->toBe(10)
        ->and($count(['max_items' => 50]))->toBe(10);
});

test('preview returns at most 10 banners', function () {
    actingAsUserWithPermissions(['page.item.view']);

    foreach (range(1, 12) as $i) {
        bannerItem($this->category->id, $this->image->id, [], "ป้าย {$i}");
    }

    $this->getJson(previewUrl(['banner_category_info_id' => $this->category->id, 'sort_by' => 'publish_desc']))
        ->assertOk()
        ->assertJsonCount(10, 'items');
});

test('slideshow text position accepts all 9 positions (default center) and saves bold flags', function () {
    actingAsUserWithPermissions(['page.item.manage', 'page.item.view']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    foreach (['top left', 'top', 'top right', 'left', 'center', 'right', 'bottom left', 'bottom', 'bottom right'] as $position) {
        $this->put($url, slideshowPayload([slideshowWidget($this->category->id, ['text_align' => $position])]))->assertSessionHasNoErrors();
    }

    $this->put($url, slideshowPayload([slideshowWidget($this->category->id, ['title_bold' => 'N', 'intro_text_bold' => 'Y'])]))->assertSessionHasNoErrors();

    $widget = PageItemWidget::whereIn('page_item_column_id', PageItemColumn::whereIn('page_item_row_id', PageItemRow::where('page_item_info_id', $this->page->id)->pluck('id'))->pluck('id'))->firstOrFail();
    $setting = PageItemWidgetSlideshowBanner::findOrFail($widget->id);

    expect($setting->text_align)->toBe('center')
        ->and($setting->title_bold)->toBe('N')
        ->and($setting->intro_text_bold)->toBe('Y');
});
