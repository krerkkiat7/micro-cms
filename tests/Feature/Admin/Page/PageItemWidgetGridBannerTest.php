<?php

use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetGridBanner;
use App\Support\PageWidget\PageWidgetRegistry;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->category = BannerCategoryInfo::create(['status' => 'Y']);
    $this->image = FileInfo::create([
        'name' => 'banner.jpg', 'hash_name' => 'banner-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $this->page = PageItemInfo::create(['status' => 'Y']);
    PageItemDetail::create(['id' => $this->page->id, 'lang' => 'th', 'title' => 'หน้าเปล่า', 'status' => 'Y']);
});

/**
 * @return array<string, mixed>
 */
function gridBannerSetting(int $categoryId, array $overrides = []): array
{
    return array_replace(PageWidgetRegistry::find('gridbanner')->defaults(), ['banner_category_info_id' => $categoryId], $overrides);
}

/**
 * @return array<string, mixed>
 */
function gridBannerPayload(int $categoryId, array $settingOverrides = [], array $widgetOverrides = []): array
{
    $widget = layoutWidget(array_replace(['widget_type' => 'gridbanner', 'setting' => gridBannerSetting($categoryId, $settingOverrides)], $widgetOverrides));

    return ['rows' => [layoutRow([layoutColumn([$widget])])]];
}

function newestGridBanner(): ?PageItemWidget
{
    return PageItemWidget::where('widget_type', 'gridbanner')->orderByDesc('id')->first();
}

function gridBannerItem(int $categoryId, ?int $imageId, array $overrides = [], string $title = 'ป้าย'): BannerItemInfo
{
    $item = BannerItemInfo::create($overrides + [
        'banner_category_info_id' => $categoryId,
        'intro_image_id' => $imageId,
        'publish_date' => now()->subDay(),
        'status' => 'Y',
    ]);
    BannerItemDetail::create(['id' => $item->id, 'lang' => 'th', 'title' => $title, 'intro_text' => "เกริ่นนำ {$title}\nบรรทัดสอง", 'status' => 'Y']);

    return $item;
}

function gridBannerPreviewUrl(array $setting): string
{
    return route('admin.page.item.widget.preview', ['widget_type' => 'gridbanner', 'setting' => $setting]);
}

// ---------------------------------------------------------------- defaults / schema

test('the defaults have no date/views/read-all and only card/row_image display types', function () {
    $d = PageWidgetRegistry::find('gridbanner')->defaults();

    expect($d['display_type'])->toBe('card')
        ->and($d)->not->toHaveKeys(['show_date', 'show_views', 'show_read_all', 'read_all_url'])
        ->and($d['show_intro_text'])->toBe('N')
        ->and($d['title_color'])->toBe('#000000')
        ->and($d['max_items'])->toBe(0)
        ->and($d['banner_category_info_id'])->toBeNull()
        ->and([$d['per_row_pc'], $d['per_row_notebook'], $d['per_row_tablet'], $d['per_row_mobile']])->toBe([4, 3, 2, 1])
        ->and($d['image_width_percent'])->toBe(20)
        ->and($d['image_background'])->toBe('#F3F4F6')
        ->and([$d['show_border'], $d['border_color'], $d['rounded_corners'], $d['item_background']])->toBe(['Y', '#E5E7EB', 'Y', '#FFFFFF']);
});

test('every setting field has a column in the table and a fillable entry on the model (no drift)', function () {
    $keys = array_keys(PageWidgetRegistry::find('gridbanner')->defaults());
    $columns = Schema::getColumnListing('page_item_widget_gridbanner');
    $fillable = (new PageItemWidgetGridBanner)->getFillable();

    expect(array_diff($keys, $columns))->toBe([])
        ->and(array_diff($keys, $fillable))->toBe([])
        ->and(count($keys))->toBe(count($columns) - 7); // id + created/updated/deleted_by + timestamps + deleted_at
});

test('gridbanner has no _detail table (no per-language fields)', function () {
    expect(Schema::hasTable('page_item_widget_gridbanner_detail'))->toBeFalse();
});

// ---------------------------------------------------------------- save

test('saving a gridbanner widget stores every setting in its own table with the same id', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), gridBannerPayload($this->category->id, [
        'sort_by' => 'order_desc', 'max_items' => 9, 'display_type' => 'row_image',
        'per_row_pc' => 5, 'per_row_notebook' => 4, 'per_row_tablet' => 3, 'per_row_mobile' => 2,
        'show_image' => 'Y', 'image_width_percent' => 35, 'aspect_ratio' => '4:3', 'image_fit' => 'contain', 'image_clickable' => 'N', 'link_target' => '_blank',
        'show_border' => 'N', 'border_color' => '#ff00ff', 'rounded_corners' => 'N', 'item_background' => 'transparent',
        'title_font_size' => 22, 'title_bold' => 'N', 'title_font_family' => 'Kanit', 'title_color' => '#ff0000', 'title_align' => 'center', 'title_clickable' => 'N', 'title_lines' => 3,
        'show_intro_text' => 'Y', 'intro_text_lines' => 1, 'intro_text_clickable' => 'Y', 'intro_text_align' => 'right',
    ]))->assertSessionHasNoErrors();

    $widget = newestGridBanner();
    $row = PageItemWidgetGridBanner::findOrFail($widget->id);

    expect($row->banner_category_info_id)->toBe($this->category->id)
        ->and($row->sort_by)->toBe('order_desc')
        ->and($row->max_items)->toBe(9)
        ->and($row->display_type)->toBe('row_image')
        ->and($row->per_row_pc)->toBe(5)
        ->and($row->per_row_mobile)->toBe(2)
        ->and($row->image_width_percent)->toBe(35)
        ->and($row->image_fit)->toBe('contain')
        ->and($row->link_target)->toBe('_blank')
        ->and([$row->show_border, $row->border_color, $row->rounded_corners, $row->item_background])->toBe(['N', '#ff00ff', 'N', 'transparent'])
        ->and($row->title_bold)->toBe('N')
        ->and($row->title_font_family)->toBe('Kanit')
        // display_type != card บังคับ show_title = Y
        ->and($row->show_title)->toBe('Y')
        ->and($row->title_lines)->toBe(3)
        ->and($row->show_intro_text)->toBe('Y')
        ->and($row->intro_text_clickable)->toBe('Y')
        ->and($row->intro_text_lines)->toBe(1)
        ->and($row->created_by)->toBe($me->id);
});

test('display_type row_image forces show_title to Y even if the request tries to turn it off', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), gridBannerPayload($this->category->id, ['display_type' => 'row_image', 'show_title' => 'N']))
        ->assertSessionHasNoErrors();

    expect(PageItemWidgetGridBanner::findOrFail(newestGridBanner()->id)->show_title)->toBe('Y');

    // การ์ด (card) ไม่ถูกบังคับ — ปิดได้ตามปกติ
    $this->put(route('admin.page.item.layout.update', $this->page->id), gridBannerPayload($this->category->id, ['display_type' => 'card', 'show_title' => 'N']))
        ->assertSessionHasNoErrors();

    expect(PageItemWidgetGridBanner::findOrFail(newestGridBanner()->id)->show_title)->toBe('N');
});

test('an empty max_items means show all (0); saving again updates the same row; removing soft deletes it', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, gridBannerPayload($this->category->id, ['max_items' => 4]))->assertSessionHasNoErrors();
    $widget = newestGridBanner();

    $this->put($url, gridBannerPayload($this->category->id, ['max_items' => null], ['id' => $widget->id]))->assertSessionHasNoErrors();
    expect(PageItemWidgetGridBanner::where('id', $widget->id)->count())->toBe(1)
        ->and(PageItemWidgetGridBanner::find($widget->id)->max_items)->toBe(0);

    $this->put($url, ['rows' => []])->assertSessionHasNoErrors();
    expect(PageItemWidgetGridBanner::find($widget->id))->toBeNull()
        ->and(PageItemWidgetGridBanner::withTrashed()->find($widget->id))->not->toBeNull();
});

test('the layout page serves the saved setting with numbers as integers and the banner category options', function () {
    actingAsUserWithPermissions(['page.item.view', 'page.item.manage']);
    $this->put(route('admin.page.item.layout.update', $this->page->id), gridBannerPayload($this->category->id, ['title_lines' => 2, 'per_row_pc' => 6]))
        ->assertSessionHasNoErrors();

    $this->get(route('admin.page.item.layout', $this->page->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.columns.0.widgets.0.widget_type', 'gridbanner')
            ->where('rows.0.columns.0.widgets.0.setting.banner_category_info_id', $this->category->id)
            ->where('rows.0.columns.0.widgets.0.setting.title_lines', 2)
            ->where('rows.0.columns.0.widgets.0.setting.per_row_pc', 6)
            ->has('widgetOptions.banner_categories')
        );
});

// ---------------------------------------------------------------- validation

test('gridbanner requires an existing, enabled banner category', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);
    $key = 'rows.0.columns.0.widgets.0.setting.banner_category_info_id';

    $this->put($url, gridBannerPayload($this->category->id, ['banner_category_info_id' => null]))->assertSessionHasErrors($key);
    $this->put($url, gridBannerPayload(999999))->assertSessionHasErrors($key);

    $this->category->update(['status' => 'N']);
    $this->put($url, gridBannerPayload($this->category->id))->assertSessionHasErrors($key);
});

test('gridbanner rejects invalid values', function (string $field, mixed $value) {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), gridBannerPayload($this->category->id, [$field => $value]))
        ->assertSessionHasErrors("rows.0.columns.0.widgets.0.setting.{$field}");
})->with([
    'display type row_date not allowed' => ['display_type', 'row_date'],
    'content align' => ['content_align', 'left'],
    'max items negative' => ['max_items', -1],
    'per row pc zero' => ['per_row_pc', 0],
    'per row notebook 7' => ['per_row_notebook', 7],
    'image width low' => ['image_width_percent', 4],
    'image width high' => ['image_width_percent', 51],
    'aspect' => ['aspect_ratio', '3:1'],
    'image fit' => ['image_fit', 'fill'],
    'link target' => ['link_target', '_top'],
    'flag' => ['title_bold', 'yes'],
    'title lines 4' => ['title_lines', 4],
    'intro lines 0' => ['intro_text_lines', 0],
    'title font' => ['title_font_family', 'Comic Sans'],
    'title color' => ['title_color', 'black'],
    'image background' => ['image_background', 'gray'],
    'border flag' => ['show_border', 'yes'],
    'border color name' => ['border_color', 'gray'],
    'border color transparent' => ['border_color', 'transparent'],
    'rounded flag' => ['rounded_corners', 'no'],
    'item background name' => ['item_background', 'white'],
]);

test('the article-only settings are not part of the banner grid', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    // ส่งฟิลด์ของ article มาด้วยก็ไม่ทำให้พัง (ถูกตัดทิ้ง) แต่ไม่มีคอลัมน์ให้เก็บ
    $this->put(route('admin.page.item.layout.update', $this->page->id), gridBannerPayload($this->category->id, ['show_read_all' => 'Y', 'show_date' => 'Y']))
        ->assertSessionHasNoErrors();

    expect(Schema::hasColumn('page_item_widget_gridbanner', 'show_read_all'))->toBeFalse()
        ->and(Schema::hasColumn('page_item_widget_gridbanner', 'show_date'))->toBeFalse();
});

test('the type of a saved gridbanner widget cannot change to the article grid', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, gridBannerPayload($this->category->id))->assertSessionHasNoErrors();
    $widget = newestGridBanner();

    $this->put($url, gridBannerPayload($this->category->id, [], ['id' => $widget->id, 'widget_type' => 'gridarticle']))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.widget_type');
});

// ---------------------------------------------------------------- preview

test('preview returns only published banners with an image, tells whether they have a link, without urls', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    gridBannerItem($cid, $this->image->id, ['url' => 'https://example.com/secret'], 'มีลิงก์');
    gridBannerItem($cid, $this->image->id, ['url' => null], 'ไม่มีลิงก์');
    gridBannerItem($cid, $this->image->id, ['status' => 'N'], 'ปิดอยู่');
    gridBannerItem($cid, $this->image->id, ['publish_date' => now()->addDay()], 'ยังไม่ถึงเวลา');
    gridBannerItem($cid, $this->image->id, ['publish_down' => now()->subHour()], 'หมดเวลาแล้ว');
    gridBannerItem($cid, null, [], 'ไม่มีรูป');
    gridBannerItem($cid, $this->image->id, [], 'ถูกลบ')->delete();
    gridBannerItem(BannerCategoryInfo::where('id', '!=', $cid)->value('id'), $this->image->id, [], 'หมวดอื่น');

    $response = $this->getJson(gridBannerPreviewUrl(['banner_category_info_id' => $cid, 'sort_by' => 'publish_desc']))->assertOk();
    $items = collect($response->json('items'));

    expect($items->pluck('has_link', 'title')->sortKeys()->all())->toBe(['มีลิงก์' => true, 'ไม่มีลิงก์' => false])
        ->and($items->first()['image'])->toBe('banner-hash.jpg')
        ->and($items->first())->not->toHaveKeys(['date', 'views'])
        ->and($response->getContent())->not->toContain('example.com');
});

test('preview sorts by publish date and by order in both directions, honours max_items and caps at 10', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    gridBannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(3), 'sort_order' => 2], 'เก่า');
    gridBannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(1), 'sort_order' => 3], 'ใหม่');
    gridBannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(2), 'sort_order' => 1], 'กลาง');

    $titles = fn (string $sort, array $extra = []) => collect($this->getJson(gridBannerPreviewUrl(['banner_category_info_id' => $cid, 'sort_by' => $sort] + $extra))->assertOk()->json('items'))->pluck('title')->all();

    expect($titles('publish_desc'))->toBe(['ใหม่', 'กลาง', 'เก่า'])
        ->and($titles('publish_asc'))->toBe(['เก่า', 'กลาง', 'ใหม่'])
        ->and($titles('order_asc'))->toBe(['กลาง', 'เก่า', 'ใหม่'])
        ->and($titles('order_desc'))->toBe(['ใหม่', 'เก่า', 'กลาง'])
        ->and($titles('publish_desc', ['max_items' => 2]))->toHaveCount(2);

    foreach (range(1, 12) as $i) {
        gridBannerItem($cid, $this->image->id, ['publish_date' => now()->subDays($i + 10)], "บทความ {$i}");
    }

    expect($titles('publish_desc'))->toHaveCount(10);
});

test('the row content alignment (top / center / bottom) is saved', function (string $align) {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), gridBannerPayload($this->category->id, ['display_type' => 'row_image', 'content_align' => $align]))
        ->assertSessionHasNoErrors();

    expect(PageItemWidgetGridBanner::findOrFail(newestGridBanner()->id)->content_align)->toBe($align);
})->with(['top', 'center', 'bottom']);
