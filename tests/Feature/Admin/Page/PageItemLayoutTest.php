<?php

use App\Models\LogBackAction;
use App\Models\PageItemColumn;
use App\Models\PageItemColumnDetail;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemRowDetail;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetDetail;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างหน้าเพจตัวอย่างพร้อมโครงสร้าง 3 แถวมาด้วย (PageSeeder)
    $this->seed(DatabaseSeeder::class);
    $this->sample = PageItemInfo::query()->firstOrFail();

    // หน้าเปล่าสำหรับเทสที่ต้องการควบคุมโครงสร้างเอง
    $this->page = PageItemInfo::create(['status' => 'Y']);
    PageItemDetail::create(['id' => $this->page->id, 'lang' => 'th', 'title' => 'หน้าเปล่า', 'status' => 'Y']);
});

/**
 * ค่าการจัดรูปแบบตัวอักษรครบ 12 ค่า (หัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ) ที่หน้าจอส่งมาเสมอ
 *
 * @return array<string, mixed>
 */
function layoutTextStyle(int $titleSize = 32, array $overrides = []): array
{
    $style = [];

    foreach (['title' => $titleSize, 'subtitle' => 20, 'intro_text' => 16] as $part => $size) {
        $style["{$part}_font_size"] = $size;
        $style["{$part}_font_family"] = 'Sarabun';
        $style["{$part}_align"] = 'center';
        $style["{$part}_color"] = '#000000';
    }

    return array_replace($style, $overrides);
}

/**
 * @return array<string, mixed>
 */
function layoutWidget(array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'show_title' => 'Y',
        'widget_type' => 'placeholder',
        'setting' => [],
        'background_color' => 'transparent',
        'detail' => ['th' => ['title' => 'วิดเจ็ต', 'subtitle' => '', 'intro_text' => ''], 'en' => ['title' => 'Widget', 'subtitle' => '', 'intro_text' => '']],
    ] + layoutTextStyle(20), $overrides);
}

/**
 * @param  list<array<string, mixed>>  $widgets
 * @return array<string, mixed>
 */
function layoutColumn(array $widgets = [], array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'show_title' => 'N',
        'column_size' => 12,
        'background_color' => 'transparent',
        'detail' => ['th' => ['title' => 'คอลัมน์', 'subtitle' => '', 'intro_text' => ''], 'en' => ['title' => 'Column', 'subtitle' => '', 'intro_text' => '']],
        'widgets' => $widgets,
    ] + layoutTextStyle(24), $overrides);
}

/**
 * @param  list<array<string, mixed>>  $columns
 * @return array<string, mixed>
 */
function layoutRow(array $columns = [], array $overrides = []): array
{
    return array_replace_recursive([
        'status' => 'Y',
        'show_title' => 'N',
        'use_container' => 'Y',
        'background_color' => 'transparent',
        'detail' => ['th' => ['title' => 'แถว', 'subtitle' => '', 'intro_text' => ''], 'en' => ['title' => 'Row', 'subtitle' => '', 'intro_text' => '']],
        'columns' => $columns,
    ] + layoutTextStyle(32), $overrides);
}

// ---------------------------------------------------------------- show

test('layout page redirects without page.item.view and for a missing page', function () {
    actingAsUserWithPermissions([]);
    $this->get(route('admin.page.item.layout', $this->sample->id))->assertRedirect(route('admin.page.item.index'));

    actingAsUserWithPermissions(['page.item.view']);
    $this->get(route('admin.page.item.layout', 999999))->assertRedirect(route('admin.page.item.index'));
});

test('layout page renders the row/column/widget tree and logs a layout view', function () {
    actingAsUserWithPermissions(['page.item.view']);

    $this->get(route('admin.page.item.layout', $this->sample->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Page/Item/Layout')
            ->where('item.id', $this->sample->id)
            ->has('rows', 3)
            ->where('rows.0.columns.0.column_size', 12)
            ->where('rows.1.columns.0.column_size', 8)
            ->where('rows.1.columns.1.column_size', 4)
            ->where('rows.2.columns', fn ($columns) => count($columns) === 3)
            ->where('rows.0.columns.0.widgets.0.widget_type', 'placeholder')
            ->where('rows.0.detail.th.title', 'ส่วนบนสุด (Hero)')
            ->where('rows.0.detail.th.subtitle', 'ยินดีต้อนรับ')
            ->where('rows.0.show_title', 'Y')
            ->where('can.manage', false)
            ->where('fonts', fn ($fonts) => in_array('Sarabun', $fonts->all(), true) && count($fonts) > 20)
            ->where('fontsUrl', fn ($url) => str_starts_with($url, 'https://fonts.bunny.net/css?family='))
        );

    expect(LogBackAction::where('module_code', 'page.item.layout')->where('action_type', 'view')->where('ref_id', $this->sample->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------- update

test('layout update requires page.item.manage', function () {
    actingAsUserWithPermissions(['page.item.view']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [layoutRow([layoutColumn()])]])
        ->assertRedirect(route('admin.page.item.index'));

    expect(PageItemRow::where('page_item_info_id', $this->page->id)->count())->toBe(0);
});

test('layout update creates the tree in order, stamps layout_updated_* and logs', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $payload = ['rows' => [
        layoutRow([
            layoutColumn([layoutWidget(), layoutWidget(['detail' => ['th' => ['title' => 'วิดเจ็ตสอง']]])], ['column_size' => 8]),
            layoutColumn([], ['column_size' => 4]),
        ], ['detail' => ['th' => ['title' => 'แถวแรก']]]),
        layoutRow([layoutColumn([layoutWidget()])], ['use_container' => 'N', 'background_color' => '#123abc']),
    ]];

    $this->put(route('admin.page.item.layout.update', $this->page->id), $payload)
        ->assertRedirect(route('admin.page.item.layout', $this->page->id))
        ->assertSessionHas('success');

    $rows = PageItemRow::where('page_item_info_id', $this->page->id)->orderBy('sort_order')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->sort_order)->toBe(0)
        ->and($rows[1]->sort_order)->toBe(1)
        ->and($rows[0]->created_by)->toBe($me->id)
        ->and($rows[1]->use_container)->toBe('N')
        ->and($rows[1]->background_color)->toBe('#123abc')
        ->and(PageItemRowDetail::where('id', $rows[0]->id)->where('lang', 'th')->value('title'))->toBe('แถวแรก')
        ->and(PageItemRowDetail::where('id', $rows[0]->id)->where('lang', 'en')->value('title'))->toBe('Row');

    $columns = PageItemColumn::where('page_item_row_id', $rows[0]->id)->orderBy('sort_order')->get();
    expect($columns)->toHaveCount(2)
        ->and($columns->pluck('column_size')->all())->toBe([8, 4])
        ->and(PageItemColumnDetail::where('id', $columns[0]->id)->count())->toBe(2);

    $widgets = PageItemWidget::where('page_item_column_id', $columns[0]->id)->orderBy('sort_order')->get();
    expect($widgets)->toHaveCount(2)
        ->and($widgets[0]->widget_type)->toBe('placeholder')
        ->and($widgets[0]->setting)->toBe([])
        ->and(PageItemWidgetDetail::where('id', $widgets[1]->id)->where('lang', 'th')->value('title'))->toBe('วิดเจ็ตสอง');

    $page = $this->page->fresh();
    expect($page->layout_updated_by)->toBe($me->id)
        ->and($page->layout_updated_at)->not->toBeNull();

    expect(LogBackAction::where('module_code', 'page.item.layout')->where('action_type', 'update')->where('ref_id', $this->page->id)->exists())->toBeTrue();
});

test('layout update keeps existing ids, reorders, and soft deletes removed items with their children', function () {
    $me = actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [
        layoutRow([layoutColumn([layoutWidget()])]),
        layoutRow([layoutColumn([layoutWidget()])]),
        layoutRow([layoutColumn([layoutWidget()])]),
    ]]);

    [$a, $b, $c] = PageItemRow::where('page_item_info_id', $this->page->id)->orderBy('sort_order')->get()->all();
    $columnOfB = PageItemColumn::where('page_item_row_id', $b->id)->firstOrFail();
    $widgetOfB = PageItemWidget::where('page_item_column_id', $columnOfB->id)->firstOrFail();
    $columnOfA = PageItemColumn::where('page_item_row_id', $a->id)->firstOrFail();

    // สลับ c ขึ้นมาก่อน a, ลบ b, และแก้ไขชื่อแถว a — ส่ง id เดิมกลับมา
    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [
        layoutRow([layoutColumn([], ['id' => PageItemColumn::where('page_item_row_id', $c->id)->value('id')])], ['id' => $c->id]),
        layoutRow([layoutColumn([layoutWidget(['id' => PageItemWidget::where('page_item_column_id', $columnOfA->id)->value('id')])], ['id' => $columnOfA->id])], [
            'id' => $a->id,
            'detail' => ['th' => ['title' => 'แถว A แก้ไข']],
        ]),
    ]])->assertSessionHasNoErrors();

    expect(PageItemRow::find($c->id)->sort_order)->toBe(0)
        ->and(PageItemRow::find($a->id)->sort_order)->toBe(1)
        ->and(PageItemRowDetail::where('id', $a->id)->where('lang', 'th')->value('title'))->toBe('แถว A แก้ไข')
        ->and(PageItemRow::where('page_item_info_id', $this->page->id)->count())->toBe(2);

    // แถว b + คอลัมน์ + widget ของมันถูก soft delete พร้อมผู้ลบ
    expect(PageItemRow::withTrashed()->find($b->id)->deleted_at)->not->toBeNull()
        ->and(PageItemRow::withTrashed()->find($b->id)->deleted_by)->toBe($me->id)
        ->and(PageItemColumn::withTrashed()->find($columnOfB->id)->deleted_at)->not->toBeNull()
        ->and(PageItemWidget::withTrashed()->find($widgetOfB->id)->deleted_at)->not->toBeNull()
        ->and(PageItemWidget::withTrashed()->find($widgetOfB->id)->deleted_by)->toBe($me->id);

    // widget ของแถว c ที่ไม่ได้ส่งมาถูกลบด้วย แต่ widget ของ a ที่ส่ง id เดิมกลับมายังอยู่
    expect(PageItemWidget::where('page_item_column_id', $columnOfA->id)->count())->toBe(1);
});

test('layout update can move a widget to another column and keeps its id', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [
        layoutRow([layoutColumn([layoutWidget()], ['column_size' => 6]), layoutColumn([], ['column_size' => 6])]),
    ]]);

    [$left, $right] = PageItemColumn::whereIn('page_item_row_id', PageItemRow::where('page_item_info_id', $this->page->id)->pluck('id'))
        ->orderBy('sort_order')->get()->all();
    $widget = PageItemWidget::where('page_item_column_id', $left->id)->firstOrFail();

    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [
        layoutRow([
            layoutColumn([], ['id' => $left->id, 'column_size' => 6]),
            layoutColumn([layoutWidget(['id' => $widget->id])], ['id' => $right->id, 'column_size' => 6]),
        ], ['id' => $left->page_item_row_id]),
    ]])->assertSessionHasNoErrors();

    expect(PageItemWidget::find($widget->id)->page_item_column_id)->toBe($right->id)
        ->and(PageItemWidget::onlyTrashed()->count())->toBe(0);
});

test('layout update with no rows clears the layout', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->sample->id), ['rows' => []])->assertSessionHasNoErrors();

    expect(PageItemRow::where('page_item_info_id', $this->sample->id)->count())->toBe(0)
        ->and(PageItemRow::onlyTrashed()->where('page_item_info_id', $this->sample->id)->count())->toBe(3);
});

test('layout update rejects ids that belong to another page', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $foreignRow = PageItemRow::where('page_item_info_id', $this->sample->id)->firstOrFail();
    $foreignColumn = PageItemColumn::where('page_item_row_id', $foreignRow->id)->firstOrFail();
    $foreignWidget = PageItemWidget::where('page_item_column_id', $foreignColumn->id)->firstOrFail();

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [layoutRow([layoutColumn()], ['id' => $foreignRow->id])]])
        ->assertInvalid(['rows']);

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [layoutRow([layoutColumn([], ['id' => $foreignColumn->id])])]])
        ->assertInvalid(['rows']);

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [layoutRow([layoutColumn([layoutWidget(['id' => $foreignWidget->id])])])]])
        ->assertInvalid(['rows']);

    expect(PageItemRow::where('page_item_info_id', $this->page->id)->count())->toBe(0);
});

test('layout update validates column size, widget type and colours', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put($url, ['rows' => [layoutRow([layoutColumn([], ['column_size' => 13])])]])
        ->assertInvalid(['rows.0.columns.0.column_size']);

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put($url, ['rows' => [layoutRow([layoutColumn([], ['column_size' => 0])])]])
        ->assertInvalid(['rows.0.columns.0.column_size']);

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put($url, ['rows' => [layoutRow([layoutColumn([layoutWidget(['widget_type' => 'nope'])])])]])
        ->assertInvalid(['rows.0.columns.0.widgets.0.widget_type']);

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put($url, ['rows' => [layoutRow([layoutColumn([], ['background_color' => 'blue; x'])], ['background_color' => 'nope'])]])
        ->assertInvalid(['rows.0.background_color', 'rows.0.columns.0.background_color']);

    expect(PageItemRow::where('page_item_info_id', $this->page->id)->count())->toBe(0);
});

test('layout update saving twice with unchanged values does not fail on the detail rows', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [layoutRow([layoutColumn([layoutWidget()])])]]);

    $row = PageItemRow::where('page_item_info_id', $this->page->id)->firstOrFail();
    $column = PageItemColumn::where('page_item_row_id', $row->id)->firstOrFail();
    $widget = PageItemWidget::where('page_item_column_id', $column->id)->firstOrFail();

    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [
        layoutRow([layoutColumn([layoutWidget(['id' => $widget->id])], ['id' => $column->id])], ['id' => $row->id]),
    ]])->assertSessionHasNoErrors();

    expect(PageItemRowDetail::where('id', $row->id)->count())->toBe(2)
        ->and(PageItemWidgetDetail::where('id', $widget->id)->count())->toBe(2);
});

// ---------------------------------------------------------------- text style / subtitle / widget background

test('layout page serves the text style defaults set by the migration (Sarabun, centered, black)', function () {
    actingAsUserWithPermissions(['page.item.view']);

    $this->get(route('admin.page.item.layout', $this->sample->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.title_font_family', 'Sarabun')
            ->where('rows.0.title_align', 'center')
            ->where('rows.0.title_color', '#000000')
            ->where('rows.0.title_font_size', 32)
            ->where('rows.0.subtitle_font_size', 20)
            ->where('rows.0.intro_text_font_size', 16)
            ->where('rows.0.columns.0.title_font_size', 24)
            ->where('rows.0.columns.0.widgets.0.title_font_size', 20)
            ->where('rows.0.columns.0.widgets.0.background_color', 'transparent')
        );
});

test('layout update saves subtitle, text style and widget background on every level', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [
        layoutRow([
            layoutColumn([
                layoutWidget([
                    'background_color' => '#ff0000',
                    'detail' => ['th' => ['subtitle' => 'รองของวิดเจ็ต']],
                ] + layoutTextStyle(18, ['subtitle_align' => 'left'])),
            ], ['detail' => ['th' => ['subtitle' => 'รองของคอลัมน์']]] + layoutTextStyle(28, ['title_font_family' => 'Prompt'])),
        ], ['detail' => ['th' => ['subtitle' => 'รองของแถว']]] + layoutTextStyle(40, ['intro_text_align' => 'right', 'title_color' => '#123abc'])),
    ]])->assertSessionHasNoErrors();

    $row = PageItemRow::where('page_item_info_id', $this->page->id)->firstOrFail();
    $column = PageItemColumn::where('page_item_row_id', $row->id)->firstOrFail();
    $widget = PageItemWidget::where('page_item_column_id', $column->id)->firstOrFail();

    expect($row->title_font_size)->toBe(40)
        ->and($row->intro_text_align)->toBe('right')
        ->and($row->title_color)->toBe('#123abc')
        ->and($column->title_font_family)->toBe('Prompt')
        ->and($column->title_font_size)->toBe(28)
        ->and($widget->title_font_size)->toBe(18)
        ->and($widget->subtitle_align)->toBe('left')
        ->and($widget->background_color)->toBe('#ff0000')
        ->and(PageItemRowDetail::where('id', $row->id)->where('lang', 'th')->value('subtitle'))->toBe('รองของแถว')
        ->and(PageItemColumnDetail::where('id', $column->id)->where('lang', 'th')->value('subtitle'))->toBe('รองของคอลัมน์')
        ->and(PageItemWidgetDetail::where('id', $widget->id)->where('lang', 'th')->value('subtitle'))->toBe('รองของวิดเจ็ต');
});

test('layout update validates text style values', function () {
    actingAsUserWithPermissions(['page.item.manage']);
    $url = route('admin.page.item.layout.update', $this->page->id);
    $back = route('admin.page.item.layout', $this->page->id);

    $this->from($back)->put($url, ['rows' => [layoutRow([], layoutTextStyle(32, ['title_font_family' => 'Comic Sans']))]])
        ->assertInvalid(['rows.0.title_font_family']);

    $this->from($back)->put($url, ['rows' => [layoutRow([], layoutTextStyle(32, ['subtitle_align' => 'justify']))]])
        ->assertInvalid(['rows.0.subtitle_align']);

    $this->from($back)->put($url, ['rows' => [layoutRow([], layoutTextStyle(7))]])
        ->assertInvalid(['rows.0.title_font_size']);

    $this->from($back)->put($url, ['rows' => [layoutRow([], layoutTextStyle(121))]])
        ->assertInvalid(['rows.0.title_font_size']);

    // สีตัวอักษรไม่มีตัวเลือก transparent
    $this->from($back)->put($url, ['rows' => [layoutRow([], layoutTextStyle(32, ['intro_text_color' => 'transparent']))]])
        ->assertInvalid(['rows.0.intro_text_color']);

    $this->from($back)->put($url, ['rows' => [layoutRow([layoutColumn([], layoutTextStyle(24, ['title_color' => 'red']))])]])
        ->assertInvalid(['rows.0.columns.0.title_color']);

    $this->from($back)->put($url, ['rows' => [layoutRow([layoutColumn([layoutWidget(layoutTextStyle(20, ['title_font_family' => 'x']))])])]])
        ->assertInvalid(['rows.0.columns.0.widgets.0.title_font_family']);

    expect(PageItemRow::where('page_item_info_id', $this->page->id)->count())->toBe(0);
});

test('layout update rejects a malformed widget background colour', function () {
    actingAsUserWithPermissions(['page.item.manage']);

    $this->from(route('admin.page.item.layout', $this->page->id))
        ->put(route('admin.page.item.layout.update', $this->page->id), ['rows' => [
            layoutRow([layoutColumn([layoutWidget(['background_color' => 'nope'])])]),
        ]])
        ->assertInvalid(['rows.0.columns.0.widgets.0.background_color']);
});
