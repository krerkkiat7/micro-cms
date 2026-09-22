<?php

use App\Models\FileInfo;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetCustomtextPart;
use App\Models\PageItemWidgetCustomtextPartFile;
use App\Support\PageWidget\PageWidgetRegistry;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->image = FileInfo::create([
        'name' => 'cover.jpg', 'hash_name' => 'cover-hash.jpg', 'extension' => 'jpg', 'path' => 'x', 'status' => 'Y',
    ]);

    $this->page = PageItemInfo::create(['status' => 'Y']);
    PageItemDetail::create(['id' => $this->page->id, 'lang' => 'th', 'title' => 'หน้าเปล่า', 'status' => 'Y']);
});

/**
 * @return array<string, mixed>
 */
function customTextPart(array $overrides = []): array
{
    return array_replace_recursive([
        'part_type' => 'text',
        'images_display_type' => null,
        'show_title' => 'Y',
        'status' => 'Y',
        'setting' => [],
        'title_font_size' => 22,
        'title_font_family' => 'Kanit',
        'title_align' => 'center',
        'title_color' => '#111111',
        'detail' => [
            'th' => ['title' => 'หัวข้อไทย', 'detail' => '<p>เนื้อหาไทย</p>'],
            'en' => ['title' => 'English title', 'detail' => '<p>English body</p>'],
        ],
        'files' => [],
    ], $overrides);
}

/**
 * @param  list<array<string, mixed>>  $parts
 * @return array<string, mixed>
 */
function customTextPayload(array $parts, array $widgetOverrides = []): array
{
    $widget = layoutWidget(array_replace(['widget_type' => 'customtext', 'setting' => ['parts' => $parts]], $widgetOverrides));

    return ['rows' => [layoutRow([layoutColumn([$widget])])]];
}

function newestCustomTextWidget(): ?PageItemWidget
{
    return PageItemWidget::where('widget_type', 'customtext')->orderByDesc('id')->first();
}

// ---------------------------------------------------------------- defaults / registration

test('customtext is registered and defaults to an empty part list', function () {
    $type = PageWidgetRegistry::find('customtext');

    expect($type)->not->toBeNull()
        ->and($type->defaults())->toBe(['parts' => []])
        ->and(PageItemWidget::allowedTypes())->toContain('customtext');
});

// ---------------------------------------------------------------- save / load

test('saving a customtext widget stores every part in its own table with a title style', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $parts = [
        customTextPart(),
        customTextPart([
            'part_type' => 'image',
            'title_font_size' => 18,
            'title_font_family' => 'Sarabun',
            'title_align' => 'left',
            'title_color' => '#000000',
            'setting' => ['alignment' => 'center', 'size' => 'large', 'show_caption' => false],
            'files' => [['file_id' => $this->image->id]],
        ]),
    ];

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload($parts))
        ->assertRedirect(route('admin.page.item.layout', $this->page->id));

    $widget = newestCustomTextWidget();
    expect($widget)->not->toBeNull();

    $rows = PageItemWidgetCustomtextPart::where('page_item_widget_id', $widget->id)->orderBy('sort_order')->get();
    expect($rows)->toHaveCount(2);

    $text = $rows[0];
    expect($text->part_type)->toBe('text')
        ->and($text->title_font_size)->toBe(22)
        ->and($text->title_font_family)->toBe('Kanit')
        ->and($text->title_align)->toBe('center')
        ->and($text->title_color)->toBe('#111111')
        ->and($text->details()->where('lang', 'th')->first()->title)->toBe('หัวข้อไทย')
        ->and($text->details()->where('lang', 'th')->first()->detail)->toBe('<p>เนื้อหาไทย</p>');

    $image = $rows[1];
    expect($image->part_type)->toBe('image')
        ->and($image->setting)->toBe(['alignment' => 'center', 'size' => 'large', 'show_caption' => false])
        ->and($image->files()->first()->file_id)->toBe($this->image->id);
});

test('saving again replaces the previous parts (whole-list replace, not a diff)', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload([customTextPart(), customTextPart()]));
    $widget = newestCustomTextWidget();
    expect(PageItemWidgetCustomtextPart::where('page_item_widget_id', $widget->id)->count())->toBe(2);

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload([customTextPart()], ['id' => $widget->id]));

    expect(PageItemWidgetCustomtextPart::where('page_item_widget_id', $widget->id)->count())->toBe(1)
        ->and(PageItemWidgetCustomtextPart::withTrashed()->where('page_item_widget_id', $widget->id)->count())->toBe(3);
});

test('the layout screen returns every saved part with its files and per-language detail', function () {
    actingAsUserWithPermissions(['page.item.manage', 'page.item.view']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload([
        customTextPart(['part_type' => 'video', 'files' => [['file_id' => $this->image->id, 'video_type' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=abc123']]]),
    ]));

    $this->get(route('admin.page.item.layout', $this->page->id))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Page/Item/Layout')
            ->where('rows.0.columns.0.widgets.0.setting.parts.0.part_type', 'video')
            ->where('rows.0.columns.0.widgets.0.setting.parts.0.detail.th.title', 'หัวข้อไทย')
            ->where('rows.0.columns.0.widgets.0.setting.parts.0.files.0.youtube_url', 'https://www.youtube.com/watch?v=abc123')
            ->where('rows.0.columns.0.widgets.0.setting.parts.0.files.0.file.id', $this->image->id)
        );
});

test('removing the widget soft deletes its parts, files and detail rows', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload([
        customTextPart(['files' => [['file_id' => $this->image->id]]]),
    ]));
    $widget = newestCustomTextWidget();
    $part = PageItemWidgetCustomtextPart::where('page_item_widget_id', $widget->id)->firstOrFail();
    $file = PageItemWidgetCustomtextPartFile::where('page_item_widget_customtext_part_id', $part->id)->firstOrFail();

    // ส่งโครงสร้างใหม่โดยไม่มี widget นี้ (แถวว่าง) — เท่ากับลบ widget ออก
    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [layoutRow([layoutColumn([])])]]);

    expect(PageItemWidget::find($widget->id))->toBeNull()
        ->and(PageItemWidgetCustomtextPart::find($part->id))->toBeNull()
        ->and(PageItemWidgetCustomtextPart::withTrashed()->find($part->id))->not->toBeNull()
        ->and(PageItemWidgetCustomtextPartFile::withTrashed()->find($file->id)->deleted_at)->not->toBeNull();
});

// ---------------------------------------------------------------- validation

test('rejects an invalid part_type', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload([
        customTextPart(['part_type' => 'document']),
    ]))->assertSessionHasErrors(['rows.0.columns.0.widgets.0.setting.parts.0.part_type']);
});

test('rejects a bad title color', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload([
        customTextPart(['title_color' => 'not-a-color']),
    ]))->assertSessionHasErrors(['rows.0.columns.0.widgets.0.setting.parts.0.title_color']);
});

test('rejects a title_font_size outside the allowed range', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), customTextPayload([
        customTextPart(['title_font_size' => 999]),
    ]))->assertSessionHasErrors(['rows.0.columns.0.widgets.0.setting.parts.0.title_font_size']);
});
