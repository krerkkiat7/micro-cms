<?php

use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\FileInfo;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetSlideshowArticle;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างหมวดหมู่ article ตัวอย่าง (ArticleSeeder) + หน้าเพจตัวอย่าง (PageSeeder)
    $this->seed(DatabaseSeeder::class);
    $this->category = ArticleCategoryInfo::query()->firstOrFail();
    $this->image = FileInfo::create([
        'name' => 'cover.jpg', 'hash_name' => 'cover-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $this->page = PageItemInfo::create(['status' => 'Y']);
    PageItemDetail::create(['id' => $this->page->id, 'lang' => 'th', 'title' => 'หน้าเปล่า', 'status' => 'Y']);
});

/**
 * @return array<string, mixed>
 */
function articleSlideshowSetting(int $categoryId, array $overrides = []): array
{
    return array_replace([
        'article_category_info_id' => $categoryId,
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
 * payload หน้าเปล่า 1 แถว 1 คอลัมน์ ที่มี widget slideshowarticle 1 ตัว
 *
 * @return array<string, mixed>
 */
function articleSlideshowPayload(int $categoryId, array $settingOverrides = [], array $widgetOverrides = []): array
{
    $widget = layoutWidget(array_replace(['widget_type' => 'slideshowarticle', 'setting' => articleSlideshowSetting($categoryId, $settingOverrides)], $widgetOverrides));

    return ['rows' => [layoutRow([layoutColumn([$widget])])]];
}

function articleItem(int $categoryId, int $imageId, array $overrides = [], string $title = 'บทความ', ?string $slug = 'auto'): ArticleItemInfo
{
    $slug = $slug === 'auto' ? 'slug-'.uniqid() : $slug; // slug ต้องไม่ซ้ำต่อภาษา (null = บทความที่ยังไม่มี slug)
    $item = ArticleItemInfo::create($overrides + [
        'article_category_info_id' => $categoryId,
        'intro_image_id' => $imageId,
        'publish_date' => now()->subDay(),
        'status' => 'Y',
    ]);
    ArticleItemDetail::create(['id' => $item->id, 'lang' => 'th', 'title' => $title, 'intro_text' => 'เกริ่นนำ '.$title, 'slug' => $slug, 'status' => 'Y']);

    return $item;
}

function newestArticleSlideshow(): ?PageItemWidget
{
    return PageItemWidget::where('widget_type', 'slideshowarticle')->orderByDesc('id')->first();
}

function articlePreviewUrl(array $setting): string
{
    return route('admin.page.item.widget.preview', ['widget_type' => 'slideshowarticle', 'setting' => $setting]);
}

// ---------------------------------------------------------------- save

test('saving a slideshowarticle widget creates its settings row with the same id as the widget', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), articleSlideshowPayload($this->category->id, [
        'sort_by' => 'publish_asc', 'autoplay_interval' => 8, 'transition_effect' => 'zoom', 'aspect_ratio' => '4:3',
        'show_intro_text' => 'Y', 'text_align' => 'bottom left', 'text_width' => 'full', 'link_target' => '_blank',
    ]))->assertSessionHasNoErrors();

    $widget = newestArticleSlideshow();
    $setting = PageItemWidgetSlideshowArticle::findOrFail($widget->id);

    expect($setting->article_category_info_id)->toBe($this->category->id)
        ->and($setting->sort_by)->toBe('publish_asc')
        ->and($setting->autoplay_interval)->toBe(8)
        ->and($setting->transition_effect)->toBe('zoom')
        ->and($setting->aspect_ratio)->toBe('4:3')
        ->and($setting->show_intro_text)->toBe('Y')
        ->and($setting->text_align)->toBe('bottom left')
        ->and($setting->text_width)->toBe('full')
        ->and($setting->link_target)->toBe('_blank')
        ->and($setting->created_by)->toBe($me->id);
});

test('saving again updates the same settings row and removing the widget soft deletes it', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, articleSlideshowPayload($this->category->id))->assertSessionHasNoErrors();
    $widget = newestArticleSlideshow();

    $this->put($url, articleSlideshowPayload($this->category->id, ['show_dots' => 'N'], ['id' => $widget->id]))->assertSessionHasNoErrors();
    expect(PageItemWidgetSlideshowArticle::where('id', $widget->id)->count())->toBe(1)
        ->and(PageItemWidgetSlideshowArticle::find($widget->id)->show_dots)->toBe('N');

    $this->put($url, ['rows' => []])->assertSessionHasNoErrors();
    expect(PageItemWidgetSlideshowArticle::find($widget->id))->toBeNull()
        ->and(PageItemWidgetSlideshowArticle::withTrashed()->find($widget->id))->not->toBeNull();
});

test('the layout page serves the saved article setting and the article category options', function () {
    actingAsUserWithPermissions(['page.item.view', 'page.item.manage']);
    $this->put(route('admin.page.item.layout.update', $this->page->id), articleSlideshowPayload($this->category->id, ['autoplay_interval' => 12]))
        ->assertSessionHasNoErrors();

    $this->get(route('admin.page.item.layout', $this->page->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.columns.0.widgets.0.widget_type', 'slideshowarticle')
            ->where('rows.0.columns.0.widgets.0.setting.article_category_info_id', $this->category->id)
            ->where('rows.0.columns.0.widgets.0.setting.autoplay_interval', 12)
            ->where('widgetOptions.article_categories', fn ($categories) => count($categories) === ArticleCategoryInfo::where('status', 'Y')->count())
            ->has('widgetOptions.banner_categories')
        );
});

// ---------------------------------------------------------------- validation

test('slideshowarticle requires an existing, enabled article category', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);
    $key = 'rows.0.columns.0.widgets.0.setting.article_category_info_id';

    $this->put($url, articleSlideshowPayload($this->category->id, ['article_category_info_id' => null]))->assertSessionHasErrors($key);
    $this->put($url, articleSlideshowPayload(999999))->assertSessionHasErrors($key);

    $this->category->update(['status' => 'N']);
    $this->put($url, articleSlideshowPayload($this->category->id))->assertSessionHasErrors($key);
});

test('slideshowarticle rejects sorts it cannot honour and out-of-range values', function (string $field, mixed $value) {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), articleSlideshowPayload($this->category->id, [$field => $value]))
        ->assertSessionHasErrors("rows.0.columns.0.widgets.0.setting.{$field}");
})->with([
    'order sort (articles have no per-item order)' => ['sort_by', 'order_asc'],
    'effect' => ['transition_effect', 'flip'],
    'ratio' => ['aspect_ratio', '3:1'],
    'interval' => ['autoplay_interval', 61],
    'speed' => ['transition_speed', 50],
    'max items negative' => ['max_items', -1],
    'title font unknown' => ['title_font_family', 'Comic Sans'],
    'intro color not hex' => ['intro_text_color', 'white'],
]);

test('max_items and the overlay text style are saved; an empty max_items means show all (0)', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, articleSlideshowPayload($this->category->id, [
        'max_items' => 4, 'title_font_size' => 28, 'title_font_family' => 'Prompt', 'title_color' => '#123456',
        'intro_text_font_size' => 12, 'intro_text_font_family' => 'Itim', 'intro_text_color' => '#ffffff',
    ]))->assertSessionHasNoErrors();

    $widget = newestArticleSlideshow();
    $row = PageItemWidgetSlideshowArticle::findOrFail($widget->id);
    expect($row->max_items)->toBe(4)
        ->and($row->title_font_size)->toBe(28)
        ->and($row->title_font_family)->toBe('Prompt')
        ->and($row->title_color)->toBe('#123456')
        ->and($row->intro_text_font_family)->toBe('Itim');

    $this->put($url, articleSlideshowPayload($this->category->id, ['max_items' => null], ['id' => $widget->id]))->assertSessionHasNoErrors();
    expect(PageItemWidgetSlideshowArticle::find($widget->id)->max_items)->toBe(0);
});

test('a banner category id is not accepted as the article category (settings are validated per type)', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $payload = articleSlideshowPayload($this->category->id);
    $payload['rows'][0]['columns'][0]['widgets'][0]['setting'] = ['banner_category_info_id' => $this->category->id] + articleSlideshowSetting($this->category->id);
    unset($payload['rows'][0]['columns'][0]['widgets'][0]['setting']['article_category_info_id']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), $payload)
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.setting.article_category_info_id');
});

test('the type of a saved slideshowarticle widget cannot change to slideshowbanner', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->put($url, articleSlideshowPayload($this->category->id))->assertSessionHasNoErrors();
    $widget = newestArticleSlideshow();

    $this->put($url, articleSlideshowPayload($this->category->id, [], ['id' => $widget->id, 'widget_type' => 'slideshowbanner']))
        ->assertSessionHasErrors('rows.0.columns.0.widgets.0.widget_type');
});

// ---------------------------------------------------------------- preview

test('preview returns only published articles with a cover image; every article has a link (no slug needed), without urls', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    articleItem($cid, $this->image->id, [], 'มีลิงก์', 'has-slug');
    articleItem($cid, $this->image->id, [], 'ไม่มี slug', null);
    articleItem($cid, $this->image->id, ['status' => 'N'], 'ปิดอยู่');
    articleItem($cid, $this->image->id, ['publish_date' => now()->addDay()], 'ยังไม่ถึงเวลา');
    articleItem($cid, $this->image->id, ['publish_down' => now()->subHour()], 'หมดเวลาแล้ว');
    articleItem($cid, $this->image->id, ['intro_image_id' => null], 'ไม่มีรูป');
    articleItem($cid, $this->image->id, [], 'ถูกลบ')->delete();
    articleItem(ArticleCategoryInfo::where('id', '!=', $cid)->value('id'), $this->image->id, [], 'หมวดอื่น');

    $response = $this->getJson(articlePreviewUrl(['article_category_info_id' => $cid, 'sort_by' => 'publish_desc']))->assertOk();

    expect(collect($response->json('items'))->pluck('has_link', 'title')->all())->toBe(['มีลิงก์' => true, 'ไม่มี slug' => true])
        ->and($response->json('items.0.image'))->toBe('cover-hash.jpg')
        ->and($response->getContent())->not->toContain('has-slug');
});

test('preview sorts by publish date in both directions and rejects an order sort', function () {
    actingAsUserWithPermissions(['page.item.view']);
    $cid = $this->category->id;

    articleItem($cid, $this->image->id, ['publish_date' => now()->subDays(3)], 'เก่า', 'a');
    articleItem($cid, $this->image->id, ['publish_date' => now()->subDays(1)], 'ใหม่', 'b');
    articleItem($cid, $this->image->id, ['publish_date' => now()->subDays(2)], 'กลาง', 'c');

    $titles = fn (string $sort) => collect($this->getJson(articlePreviewUrl(['article_category_info_id' => $cid, 'sort_by' => $sort]))->json('items'))->pluck('title')->all();

    expect($titles('publish_desc'))->toBe(['ใหม่', 'กลาง', 'เก่า'])
        ->and($titles('publish_asc'))->toBe(['เก่า', 'กลาง', 'ใหม่']);

    $this->getJson(articlePreviewUrl(['article_category_info_id' => $cid, 'sort_by' => 'order_asc']))->assertStatus(422);
});

test('preview honours max_items (0 or empty = all, capped at the preview limit of 10)', function () {
    actingAsUserWithPermissions(['page.item.view']);
    foreach (range(1, 12) as $i) {
        articleItem($this->category->id, $this->image->id, [], "บทความ {$i}");
    }
    $count = fn (array $extra) => count($this->getJson(articlePreviewUrl(['article_category_info_id' => $this->category->id, 'sort_by' => 'publish_desc'] + $extra))->assertOk()->json('items'));

    expect($count(['max_items' => 2]))->toBe(2)
        ->and($count(['max_items' => 0]))->toBe(10)
        ->and($count([]))->toBe(10);
});

test('preview returns at most 10 articles', function () {
    actingAsUserWithPermissions(['page.item.view']);

    foreach (range(1, 12) as $i) {
        articleItem($this->category->id, $this->image->id, [], "บทความ {$i}", "slug-{$i}");
    }

    $this->getJson(articlePreviewUrl(['article_category_info_id' => $this->category->id, 'sort_by' => 'publish_desc']))
        ->assertOk()
        ->assertJsonCount(10, 'items');
});
