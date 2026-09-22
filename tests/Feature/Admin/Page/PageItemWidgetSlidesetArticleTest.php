<?php

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\FileInfo;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetSlidesetArticle;
use App\Models\PageItemWidgetSlidesetArticleDetail;
use App\Support\PageWidget\PageWidgetRegistry;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    // หมวดหมู่ใหม่ที่ไม่มีบทความตัวอย่างของ ArticleSeeder (Slideset แสดงบทความที่ไม่มีรูปด้วย จึงต้องแยกจากหมวดที่มีข้อมูลตัวอย่าง)
    $this->category = ArticleCategoryInfo::create(['status' => 'Y']);
    $this->image = FileInfo::create([
        'name' => 'cover.jpg', 'hash_name' => 'cover-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $this->page = PageItemInfo::create(['status' => 'Y']);
    PageItemDetail::create(['id' => $this->page->id, 'lang' => 'th', 'title' => 'หน้าเปล่า', 'status' => 'Y']);
});

/**
 * ค่าตั้งค่าครบทุกฟิลด์ = ค่าเริ่มต้นของประเภท + หมวดหมู่ + ค่าที่ทับ
 *
 * @return array<string, mixed>
 */
function slidesetSetting(int $categoryId, array $overrides = []): array
{
    return array_replace(PageWidgetRegistry::find('slidesetarticle')->defaults(), ['article_category_info_id' => $categoryId], $overrides);
}

/**
 * @return array<string, mixed>
 */
function slidesetPayload(int $categoryId, array $settingOverrides = [], array $widgetOverrides = []): array
{
    $widget = layoutWidget(array_replace(['widget_type' => 'slidesetarticle', 'setting' => slidesetSetting($categoryId, $settingOverrides)], $widgetOverrides));

    return ['rows' => [layoutRow([layoutColumn([$widget])])]];
}

function newestSlideset(): ?PageItemWidget
{
    return PageItemWidget::where('widget_type', 'slidesetarticle')->orderByDesc('id')->first();
}

function slidesetArticle(int $categoryId, ?int $imageId, array $overrides = [], string $title = 'บทความ'): ArticleItemInfo
{
    $item = ArticleItemInfo::create($overrides + [
        'article_category_info_id' => $categoryId,
        'intro_image_id' => $imageId,
        'publish_date' => now()->subDay(),
        'status' => 'Y',
    ]);
    ArticleItemDetail::create(['id' => $item->id, 'lang' => 'th', 'title' => $title, 'intro_text' => "เกริ่นนำ {$title}\nบรรทัดสอง", 'slug' => 'slug-'.uniqid(), 'status' => 'Y']);

    return $item;
}

function slidesetPreviewUrl(array $setting): string
{
    return route('admin.page.item.widget.preview', ['widget_type' => 'slidesetarticle', 'setting' => $setting]);
}

// ---------------------------------------------------------------- defaults / schema

test('the defaults follow the requested spec', function () {
    $d = PageWidgetRegistry::find('slidesetarticle')->defaults();

    expect($d['title_lines'])->toBe(1)
        ->and($d['intro_text_lines'])->toBe(2)
        ->and($d['title_color'])->toBe('#000000')
        ->and($d['intro_text_color'])->toBe('#000000')
        ->and($d['date_color'])->toBe('#667085')
        ->and($d['views_color'])->toBe('#667085')
        ->and($d['max_items'])->toBe(0)
        ->and($d['article_category_info_id'])->toBeNull()
        ->and([$d['per_row_pc'], $d['per_row_notebook'], $d['per_row_tablet'], $d['per_row_mobile']])->toBe([4, 3, 2, 1])
        ->and($d['image_background'])->toBe('#F3F4F6')
        ->and($d['show_read_all'])->toBe('N')
        ->and($d['read_all_text'])->toBe(['th' => '', 'en' => ''])
        ->and([$d['show_border'], $d['border_color'], $d['rounded_corners'], $d['item_background']])->toBe(['Y', '#E5E7EB', 'Y', '#FFFFFF'])
        ->and([$d['read_all_font_size'], $d['read_all_font_family'], $d['read_all_color'], $d['read_all_background']])->toBe([14, 'Sarabun', '#FFFFFF', '#1F2937']);
});

test('every setting field has a column in the table and a fillable entry on the model (no drift)', function () {
    // ฟิลด์แยกภาษา (read_all_text) อยู่ตาราง detail ไม่ใช่คอลัมน์ของตารางตั้งค่า
    $keys = array_values(array_diff(array_keys(PageWidgetRegistry::find('slidesetarticle')->defaults()), ['read_all_text']));
    $columns = Schema::getColumnListing('page_item_widget_slidesetarticle');
    $fillable = (new PageItemWidgetSlidesetArticle)->getFillable();

    expect(array_diff($keys, $columns))->toBe([])
        ->and(array_diff($keys, $fillable))->toBe([])
        ->and(count($keys))->toBe(count($columns) - 7); // id + created/updated/deleted_by + timestamps + deleted_at
});

// ---------------------------------------------------------------- save

test('saving a slidesetarticle widget stores every setting in its own table with the same id', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, [
        'sort_by' => 'publish_asc', 'max_items' => 9, 'autoplay' => 'Y', 'autoplay_interval' => 7, 'transition_speed' => 800,
        'per_row_pc' => 5, 'per_row_notebook' => 4, 'per_row_tablet' => 3, 'per_row_mobile' => 2,
        'show_image' => 'N', 'aspect_ratio' => '4:3', 'image_fit' => 'contain', 'image_clickable' => 'N', 'link_target' => '_blank',
        'title_font_size' => 22, 'title_bold' => 'N', 'title_font_family' => 'Kanit', 'title_color' => '#ff0000', 'title_align' => 'center', 'title_clickable' => 'N', 'title_lines' => 3,
        'intro_text_lines' => 1, 'intro_text_clickable' => 'Y', 'intro_text_align' => 'right',
        'show_date' => 'N', 'date_font_size' => 13, 'date_bold' => 'Y', 'date_color' => '#111111',
        'show_views' => 'Y', 'views_font_family' => 'Mitr', 'views_bold' => 'Y',
    ]))->assertSessionHasNoErrors();

    $widget = newestSlideset();
    $row = PageItemWidgetSlidesetArticle::findOrFail($widget->id);

    expect($row->article_category_info_id)->toBe($this->category->id)
        ->and($row->sort_by)->toBe('publish_asc')
        ->and($row->max_items)->toBe(9)
        ->and($row->autoplay)->toBe('Y')
        ->and($row->per_row_pc)->toBe(5)
        ->and($row->per_row_mobile)->toBe(2)
        ->and($row->show_image)->toBe('N')
        ->and($row->image_fit)->toBe('contain')
        ->and($row->link_target)->toBe('_blank')
        ->and($row->title_bold)->toBe('N')
        ->and($row->title_font_family)->toBe('Kanit')
        ->and($row->title_align)->toBe('center')
        ->and($row->title_lines)->toBe(3)
        ->and($row->intro_text_clickable)->toBe('Y')
        ->and($row->intro_text_lines)->toBe(1)
        ->and($row->show_date)->toBe('N')
        ->and($row->date_bold)->toBe('Y')
        ->and($row->show_views)->toBe('Y')
        ->and($row->views_font_family)->toBe('Mitr')
        ->and($row->created_by)->toBe($me->id);
});

test('an empty max_items means show all (0); saving again updates the same row; removing soft deletes it', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slidesetPayload($this->category->id, ['max_items' => 4]))->assertSessionHasNoErrors();
    $widget = newestSlideset();

    $this->put($url, slidesetPayload($this->category->id, ['max_items' => null], ['id' => $widget->id]))->assertSessionHasNoErrors();
    expect(PageItemWidgetSlidesetArticle::where('id', $widget->id)->count())->toBe(1)
        ->and(PageItemWidgetSlidesetArticle::find($widget->id)->max_items)->toBe(0);

    $this->put($url, ['rows' => []])->assertSessionHasNoErrors();
    expect(PageItemWidgetSlidesetArticle::find($widget->id))->toBeNull()
        ->and(PageItemWidgetSlidesetArticle::withTrashed()->find($widget->id))->not->toBeNull();
});

test('the layout page serves the saved setting with numbers as integers and the article category options', function () {
    actingAsUserWithPermissions(['page.item.view', 'page.item.manage']);
    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, ['title_lines' => 2, 'per_row_pc' => 6]))
        ->assertSessionHasNoErrors();

    $this->get(route('admin.page.item.layout', $this->page->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.columns.0.widgets.0.widget_type', 'slidesetarticle')
            ->where('rows.0.columns.0.widgets.0.setting.article_category_info_id', $this->category->id)
            ->where('rows.0.columns.0.widgets.0.setting.title_lines', 2)
            ->where('rows.0.columns.0.widgets.0.setting.per_row_pc', 6)
            ->where('rows.0.columns.0.widgets.0.setting.date_color', '#667085')
            ->has('widgetOptions.article_categories')
        );
});

// ---------------------------------------------------------------- validation

test('slidesetarticle requires an existing, enabled article category', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);
    $key = 'rows.0.columns.0.widgets.0.setting.article_category_info_id';

    $this->put($url, slidesetPayload($this->category->id, ['article_category_info_id' => null]))->assertSessionHasErrors($key);
    $this->put($url, slidesetPayload(999999))->assertSessionHasErrors($key);

    $this->category->update(['status' => 'N']);
    $this->put($url, slidesetPayload($this->category->id))->assertSessionHasErrors($key);
});

test('slidesetarticle rejects invalid values', function (string $field, mixed $value) {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, [$field => $value]))
        ->assertSessionHasErrors("rows.0.columns.0.widgets.0.setting.{$field}");
})->with([
    'order sort (articles have no per-item order)' => ['sort_by', 'order_asc'],
    'max items negative' => ['max_items', -1],
    'interval' => ['autoplay_interval', 0],
    'speed' => ['transition_speed', 5000],
    'per row pc zero' => ['per_row_pc', 0],
    'per row notebook 7' => ['per_row_notebook', 7],
    'per row tablet text' => ['per_row_tablet', 'x'],
    'per row mobile empty' => ['per_row_mobile', null],
    'aspect' => ['aspect_ratio', '3:1'],
    'image fit' => ['image_fit', 'fill'],
    'link target' => ['link_target', '_top'],
    'flag' => ['title_bold', 'yes'],
    'title lines 0' => ['title_lines', 0],
    'title lines 4' => ['title_lines', 4],
    'intro lines 4' => ['intro_text_lines', 4],
    'title align' => ['title_align', 'justify'],
    'intro align' => ['intro_text_align', 'middle'],
    'title font' => ['title_font_family', 'Comic Sans'],
    'title size low' => ['title_font_size', 7],
    'intro size high' => ['intro_text_font_size', 121],
    'date font' => ['date_font_family', 'Comic Sans'],
    'date size' => ['date_font_size', 500],
    'date color' => ['date_color', 'gray'],
    'views color transparent' => ['views_color', 'transparent'],
    'title color' => ['title_color', 'black'],
    'image background' => ['image_background', 'gray'],
    'read all position' => ['read_all_position', 'middle_left'],
    'read all icon' => ['read_all_icon', 'star'],
    'read all icon position' => ['read_all_icon_position', 'above'],
    'read all style' => ['read_all_style', 'rounded'],
    'read all target' => ['read_all_link_target', '_top'],
    'read all font' => ['read_all_font_family', 'Comic Sans'],
    'read all size low' => ['read_all_font_size', 7],
    'read all size high' => ['read_all_font_size', 121],
    'read all color name' => ['read_all_color', 'white'],
    'read all color transparent' => ['read_all_color', 'transparent'],
    'read all background name' => ['read_all_background', 'black'],
    'border flag' => ['show_border', 'yes'],
    'border color name' => ['border_color', 'gray'],
    'border color transparent' => ['border_color', 'transparent'],
    'rounded flag' => ['rounded_corners', 'no'],
    'item background name' => ['item_background', 'white'],
    'read all url without scheme' => ['read_all_url', 'example.com/news'],
]);

test('the card box border / rounded corners and the read-all button text style are saved', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, [
        'show_border' => 'N', 'border_color' => '#ff00ff', 'rounded_corners' => 'N', 'item_background' => 'transparent',
        'show_read_all' => 'Y', 'read_all_url' => '/th/news', 'read_all_style' => 'pill',
        'read_all_font_size' => 18, 'read_all_font_family' => 'Kanit', 'read_all_color' => '#ffeecc', 'read_all_background' => '#123456',
    ]))->assertSessionHasNoErrors();

    $row = PageItemWidgetSlidesetArticle::findOrFail(newestSlideset()->id);

    expect([$row->show_border, $row->border_color, $row->rounded_corners, $row->item_background])->toBe(['N', '#ff00ff', 'N', 'transparent'])
        ->and([$row->read_all_font_size, $row->read_all_font_family, $row->read_all_color, $row->read_all_background])->toBe([18, 'Kanit', '#ffeecc', '#123456']);
});

test('the read-all text is limited to 100 characters per language', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, ['read_all_text' => ['th' => 'ก'.str_repeat('ข', 100)]]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.read_all_text.th');
});

test('the image background accepts a hex colour or transparent', function (string $color) {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, ['image_fit' => 'contain', 'image_background' => $color]))
        ->assertSessionHasNoErrors();

    expect(PageItemWidgetSlidesetArticle::findOrFail(newestSlideset()->id)->image_background)->toBe($color);
})->with(['#F3F4F6', '#ffffff', 'transparent']);

test('the read-all button settings are saved with a per-language text in the detail table', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, [
        'show_read_all' => 'Y', 'read_all_position' => 'top_right', 'read_all_icon' => 'plus_circle', 'read_all_icon_position' => 'before',
        'read_all_style' => 'pill', 'read_all_url' => 'https://example.com/news', 'read_all_link_target' => '_blank',
        'read_all_text' => ['th' => '  ดูข่าวทั้งหมด  ', 'en' => 'View all news'],
    ]))->assertSessionHasNoErrors();

    $widget = newestSlideset();
    $row = PageItemWidgetSlidesetArticle::findOrFail($widget->id);
    expect($row->show_read_all)->toBe('Y')
        ->and($row->read_all_position)->toBe('top_right')
        ->and($row->read_all_icon)->toBe('plus_circle')
        ->and($row->read_all_icon_position)->toBe('before')
        ->and($row->read_all_style)->toBe('pill')
        ->and($row->read_all_url)->toBe('https://example.com/news')
        ->and($row->read_all_link_target)->toBe('_blank')
        ->and(PageItemWidgetSlidesetArticleDetail::where('id', $widget->id)->where('lang', 'th')->value('read_all_text'))->toBe('ดูข่าวทั้งหมด')
        ->and(PageItemWidgetSlidesetArticleDetail::where('id', $widget->id)->where('lang', 'en')->value('read_all_text'))->toBe('View all news');

    // แก้ข้อความซ้ำ = update แถวเดิม (composite key) ไม่สร้างซ้ำ; ล้างข้อความ = null
    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, [
        'show_read_all' => 'Y', 'read_all_url' => '/th/news', 'read_all_text' => ['th' => '', 'en' => 'All']],
        ['id' => $widget->id],
    ))->assertSessionHasNoErrors();

    expect(PageItemWidgetSlidesetArticleDetail::where('id', $widget->id)->count())->toBe(2)
        ->and(PageItemWidgetSlidesetArticleDetail::where('id', $widget->id)->where('lang', 'th')->value('read_all_text'))->toBeNull()
        ->and(PageItemWidgetSlidesetArticleDetail::where('id', $widget->id)->where('lang', 'en')->value('read_all_text'))->toBe('All');
});

test('the read-all url is required only when the button is shown; an empty url is stored as null', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slidesetPayload($this->category->id, ['show_read_all' => 'Y', 'read_all_url' => '']))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.read_all_url');
    $this->put($url, slidesetPayload($this->category->id, ['show_read_all' => 'Y', 'read_all_url' => null]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.read_all_url');

    foreach (['/th/news', '#top', 'mailto:a@b.com', 'tel:0812345678', 'HTTP://EXAMPLE.COM'] as $ok) {
        $this->put($url, slidesetPayload($this->category->id, ['show_read_all' => 'Y', 'read_all_url' => $ok]))->assertSessionHasNoErrors();
    }

    $this->put($url, slidesetPayload($this->category->id, ['show_read_all' => 'N', 'read_all_url' => '']))->assertSessionHasNoErrors();
    expect(PageItemWidgetSlidesetArticle::findOrFail(newestSlideset()->id)->read_all_url)->toBeNull();
});

test('the layout page serves the read-all text for every enabled language', function () {
    actingAsUserWithPermissions(['page.item.view', 'page.item.manage']);
    $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, [
        'show_read_all' => 'Y', 'read_all_url' => '/th/news', 'read_all_text' => ['th' => 'ดูทั้งหมด'],
    ]))->assertSessionHasNoErrors();

    $this->get(route('admin.page.item.layout', $this->page->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.columns.0.widgets.0.setting.read_all_text.th', 'ดูทั้งหมด')
            ->where('rows.0.columns.0.widgets.0.setting.read_all_text.en', '')
            ->where('rows.0.columns.0.widgets.0.setting.read_all_url', '/th/news')
        );
});

test('validation errors use the field labels', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $errors = $this->put(route('admin.page.item.layout.update', $this->page->id), slidesetPayload($this->category->id, ['per_row_pc' => 9]))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.per_row_pc')
        ->getSession()->get('errors')->first('rows.0.columns.0.widgets.0.setting.per_row_pc');

    expect($errors)->toContain('จำนวนที่แสดงต่อแถว (PC)')->toContain('1 - 6');
});

test('the type of a saved slidesetarticle widget cannot change', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, slidesetPayload($this->category->id))->assertSessionHasNoErrors();
    $widget = newestSlideset();

    $this->put($url, slidesetPayload($this->category->id, [], ['id' => $widget->id, 'widget_type' => 'slideshowarticle']))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.widget_type');
});

// ---------------------------------------------------------------- preview

test('preview keeps articles without a cover image, returns date/views and the intro with its line breaks, without urls', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    slidesetArticle($cid, $this->image->id, ['publish_date' => '2026-03-15 10:00:00', 'view_amount' => 42], 'มีรูป');
    slidesetArticle($cid, null, ['publish_date' => '2026-03-14 10:00:00'], 'ไม่มีรูป');
    slidesetArticle($cid, $this->image->id, ['status' => 'N'], 'ปิดอยู่');
    slidesetArticle($cid, $this->image->id, ['publish_date' => now()->addDay()], 'ยังไม่ถึงเวลา');
    slidesetArticle($cid, $this->image->id, ['publish_down' => now()->subHour()], 'หมดเวลาแล้ว');
    slidesetArticle($cid, $this->image->id, [], 'ถูกลบ')->delete();
    slidesetArticle(ArticleCategoryInfo::where('id', '!=', $cid)->value('id'), $this->image->id, [], 'หมวดอื่น');

    $response = $this->getJson(slidesetPreviewUrl(['article_category_info_id' => $cid, 'sort_by' => 'publish_desc']))->assertOk();
    $items = collect($response->json('items'));

    expect($items->pluck('title')->all())->toBe(['มีรูป', 'ไม่มีรูป'])
        ->and($items[0]['image'])->toBe('cover-hash.jpg')
        ->and($items[1]['image'])->toBeNull()
        ->and($items[0]['date'])->toBe('2026-03-15')
        ->and($items[0]['views'])->toBe(42)
        ->and($items[0]['intro_text'])->toBe("เกริ่นนำ มีรูป\nบรรทัดสอง")
        ->and($response->getContent())->not->toContain('slug-');
});

test('preview sorts by publish date both ways, honours max_items and caps at 10', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    foreach (range(1, 12) as $i) {
        slidesetArticle($cid, $this->image->id, ['publish_date' => now()->subDays($i)], "บทความ {$i}");
    }

    $titles = fn (array $extra) => collect($this->getJson(slidesetPreviewUrl(array_replace(['article_category_info_id' => $cid, 'sort_by' => 'publish_desc'], $extra)))->assertOk()->json('items'))->pluck('title')->all();

    expect($titles([])[0])->toBe('บทความ 1')
        ->and($titles(['sort_by' => 'publish_asc'])[0])->toBe('บทความ 12')
        ->and($titles(['max_items' => 3]))->toHaveCount(3)
        ->and($titles(['max_items' => 0]))->toHaveCount(10)
        ->and($titles(['max_items' => 50]))->toHaveCount(10);

    $this->getJson(slidesetPreviewUrl(['article_category_info_id' => $cid, 'sort_by' => 'order_asc']))->assertStatus(422);
});
