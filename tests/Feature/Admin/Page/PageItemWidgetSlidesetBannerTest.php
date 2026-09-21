<?php

use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use App\Models\FileInfo;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetSlidesetBanner;
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
function slidesetBannerSetting(int $categoryId, array $overrides = []): array
{
    return array_replace(PageWidgetRegistry::find('slidesetbanner')->defaults(), ['banner_category_info_id' => $categoryId], $overrides);
}

/**
 * @return array<string, mixed>
 */
function slidesetBannerPayload(int $categoryId, array $settingOverrides = [], array $widgetOverrides = []): array
{
    $widget = layoutWidget(array_replace(['widget_type' => 'slidesetbanner', 'setting' => slidesetBannerSetting($categoryId, $settingOverrides)], $widgetOverrides));

    return ['rows' => [layoutRow([layoutColumn([$widget])])]];
}

function newestSlidesetBanner(): ?PageItemWidget
{
    return PageItemWidget::where('widget_type', 'slidesetbanner')->orderByDesc('id')->first();
}

function slidesetBannerItem(int $categoryId, ?int $imageId, array $overrides = [], string $title = 'ป้าย'): BannerItemInfo
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

function slidesetBannerPreviewUrl(array $setting): string
{
    return route('admin.page.item.widget.preview', ['widget_type' => 'slidesetbanner', 'setting' => $setting]);
}

// ---------------------------------------------------------------- defaults / schema

test('the defaults differ from the article slideset: intro hidden, no date / views / read-all', function () {
    $d = PageWidgetRegistry::find('slidesetbanner')->defaults();

    expect($d['show_intro_text'])->toBe('N')
        ->and($d['show_title'])->toBe('Y')
        ->and($d['title_lines'])->toBe(1)
        ->and($d['intro_text_lines'])->toBe(2)
        ->and($d['image_background'])->toBe('#F3F4F6')
        ->and($d['banner_category_info_id'])->toBeNull()
        ->and($d)->not->toHaveKeys(['show_date', 'date_color', 'show_views', 'views_color', 'show_read_all', 'read_all_text', 'article_category_info_id']);
});

test('every setting field has a column in the table and a fillable entry on the model (no drift)', function () {
    $keys = array_keys(PageWidgetRegistry::find('slidesetbanner')->defaults());
    $columns = Schema::getColumnListing('page_item_widget_slidesetbanner');
    $fillable = (new PageItemWidgetSlidesetBanner)->getFillable();

    expect(array_diff($keys, $columns))->toBe([])
        ->and(array_diff($keys, $fillable))->toBe([])
        ->and(count($keys))->toBe(count($columns) - 7); // id + created/updated/deleted_by + timestamps + deleted_at
});

// ---------------------------------------------------------------- save

test('saving a slidesetbanner widget stores every setting in its own table with the same id', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetBannerPayload($this->category->id, [
        'sort_by' => 'order_desc', 'max_items' => 6, 'autoplay' => 'Y', 'per_row_pc' => 5, 'per_row_mobile' => 2,
        'aspect_ratio' => '1:1', 'image_fit' => 'contain', 'image_background' => 'transparent', 'link_target' => '_blank',
        'show_intro_text' => 'Y', 'intro_text_lines' => 3, 'title_font_family' => 'Kanit', 'title_color' => '#ff0000',
    ]))->assertSessionHasNoErrors();

    $row = PageItemWidgetSlidesetBanner::findOrFail(newestSlidesetBanner()->id);

    expect($row->banner_category_info_id)->toBe($this->category->id)
        ->and($row->sort_by)->toBe('order_desc')
        ->and($row->max_items)->toBe(6)
        ->and($row->per_row_pc)->toBe(5)
        ->and($row->image_fit)->toBe('contain')
        ->and($row->image_background)->toBe('transparent')
        ->and($row->link_target)->toBe('_blank')
        ->and($row->show_intro_text)->toBe('Y')
        ->and($row->intro_text_lines)->toBe(3)
        ->and($row->title_font_family)->toBe('Kanit')
        ->and($row->created_by)->toBe($me->id);
});

test('an empty max_items means show all; saving again updates the same row; removing soft deletes it', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slidesetBannerPayload($this->category->id, ['max_items' => 4]))->assertSessionHasNoErrors();
    $widget = newestSlidesetBanner();

    $this->put($url, slidesetBannerPayload($this->category->id, ['max_items' => null], ['id' => $widget->id]))->assertSessionHasNoErrors();
    expect(PageItemWidgetSlidesetBanner::where('id', $widget->id)->count())->toBe(1)
        ->and(PageItemWidgetSlidesetBanner::find($widget->id)->max_items)->toBe(0);

    $this->put($url, ['rows' => []])->assertSessionHasNoErrors();
    expect(PageItemWidgetSlidesetBanner::find($widget->id))->toBeNull()
        ->and(PageItemWidgetSlidesetBanner::withTrashed()->find($widget->id))->not->toBeNull();
});

test('the layout page serves the saved setting and the banner category options', function () {
    actingAsUserWithPermissions(['page.item.view', 'page.item.manage']);
    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetBannerPayload($this->category->id, ['per_row_pc' => 6]))
        ->assertSessionHasNoErrors();

    $this->get(route('admin.page.item.layout', $this->page->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.columns.0.widgets.0.widget_type', 'slidesetbanner')
            ->where('rows.0.columns.0.widgets.0.setting.banner_category_info_id', $this->category->id)
            ->where('rows.0.columns.0.widgets.0.setting.per_row_pc', 6)
            ->where('rows.0.columns.0.widgets.0.setting.show_intro_text', 'N')
            ->has('widgetOptions.banner_categories')
        );
});

// ---------------------------------------------------------------- validation

test('slidesetbanner requires an existing, enabled banner category', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);
    $key = 'rows.0.columns.0.widgets.0.setting.banner_category_info_id';

    $this->put($url, slidesetBannerPayload($this->category->id, ['banner_category_info_id' => null]))->assertSessionHasErrors($key);
    $this->put($url, slidesetBannerPayload(999999))->assertSessionHasErrors($key);

    $this->category->update(['status' => 'N']);
    $this->put($url, slidesetBannerPayload($this->category->id))->assertSessionHasErrors($key);
});

test('slidesetbanner rejects invalid values', function (string $field, mixed $value) {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetBannerPayload($this->category->id, [$field => $value]))
        ->assertSessionHasErrors("rows.0.columns.0.widgets.0.setting.{$field}");
})->with([
    'sort' => ['sort_by', 'random'],
    'max items negative' => ['max_items', -1],
    'per row zero' => ['per_row_pc', 0],
    'per row 7' => ['per_row_tablet', 7],
    'aspect' => ['aspect_ratio', '3:1'],
    'image fit' => ['image_fit', 'fill'],
    'image background' => ['image_background', 'gray'],
    'title lines 4' => ['title_lines', 4],
    'intro lines 0' => ['intro_text_lines', 0],
    'title font' => ['title_font_family', 'Comic Sans'],
    'title color' => ['title_color', 'black'],
    'link target' => ['link_target', '_top'],
    'flag' => ['show_arrows', 'yes'],
]);

test('the article-only settings are not part of the banner slideset', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    // ส่งฟิลด์ของ article มาด้วยก็ไม่ทำให้พัง (ถูกตัดทิ้ง) แต่ไม่มีคอลัมน์ให้เก็บ
    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetBannerPayload($this->category->id, ['show_read_all' => 'Y', 'show_date' => 'Y']))
        ->assertSessionHasNoErrors();

    expect(Schema::hasColumn('page_item_widget_slidesetbanner', 'show_read_all'))->toBeFalse()
        ->and(Schema::hasColumn('page_item_widget_slidesetbanner', 'show_date'))->toBeFalse();
});

test('the type of a saved slidesetbanner widget cannot change to the article slideset', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slidesetBannerPayload($this->category->id))->assertSessionHasNoErrors();
    $widget = newestSlidesetBanner();

    $this->put($url, slidesetBannerPayload($this->category->id, [], ['id' => $widget->id, 'widget_type' => 'slidesetarticle']))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.widget_type');
});

// ---------------------------------------------------------------- preview

test('preview returns only published banners with an image, tells whether they have a link, without urls', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    slidesetBannerItem($cid, $this->image->id, ['url' => 'https://example.com/secret'], 'มีลิงก์');
    slidesetBannerItem($cid, $this->image->id, ['url' => null], 'ไม่มีลิงก์');
    slidesetBannerItem($cid, $this->image->id, ['status' => 'N'], 'ปิดอยู่');
    slidesetBannerItem($cid, $this->image->id, ['publish_date' => now()->addDay()], 'ยังไม่ถึงเวลา');
    slidesetBannerItem($cid, $this->image->id, ['publish_down' => now()->subHour()], 'หมดเวลาแล้ว');
    slidesetBannerItem($cid, null, [], 'ไม่มีรูป');
    slidesetBannerItem($cid, $this->image->id, [], 'ถูกลบ')->delete();
    slidesetBannerItem(BannerCategoryInfo::where('id', '!=', $cid)->value('id'), $this->image->id, [], 'หมวดอื่น');

    $response = $this->getJson(slidesetBannerPreviewUrl(['banner_category_info_id' => $cid, 'sort_by' => 'publish_desc']))->assertOk();
    $items = collect($response->json('items'));

    expect($items->pluck('has_link', 'title')->sortKeys()->all())->toBe(['มีลิงก์' => true, 'ไม่มีลิงก์' => false])
        ->and($items->first()['image'])->toBe('banner-hash.jpg')
        ->and($items->first())->not->toHaveKeys(['date', 'views'])
        ->and($response->getContent())->not->toContain('example.com');
});

test('preview sorts by publish date and by order in both directions, honours max_items and caps at 10', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    slidesetBannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(3), 'sort_order' => 2], 'เก่า');
    slidesetBannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(1), 'sort_order' => 3], 'ใหม่');
    slidesetBannerItem($cid, $this->image->id, ['publish_date' => now()->subDays(2), 'sort_order' => 1], 'กลาง');

    $titles = fn (string $sort, array $extra = []) => collect($this->getJson(slidesetBannerPreviewUrl(['banner_category_info_id' => $cid, 'sort_by' => $sort] + $extra))->assertOk()->json('items'))->pluck('title')->all();

    expect($titles('publish_desc'))->toBe(['ใหม่', 'กลาง', 'เก่า'])
        ->and($titles('publish_asc'))->toBe(['เก่า', 'กลาง', 'ใหม่'])
        ->and($titles('order_asc'))->toBe(['กลาง', 'เก่า', 'ใหม่'])
        ->and($titles('order_desc'))->toBe(['ใหม่', 'เก่า', 'กลาง'])
        ->and($titles('publish_desc', ['max_items' => 2]))->toHaveCount(2);

    foreach (range(1, 12) as $i) {
        slidesetBannerItem($cid, $this->image->id, [], "เพิ่ม {$i}");
    }

    expect($titles('publish_desc'))->toHaveCount(10)
        ->and($titles('publish_desc', ['max_items' => 0]))->toHaveCount(10);
});
