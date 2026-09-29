<?php

use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use App\Models\LogBackAction;
use App\Models\PageItemInfo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// รายงานของโมดูลหน้าเพจ (เข้าชม, ไม่มีหมวดหมู่) และป้ายโฆษณา (คลิก, มีหมวดหมู่) — ใช้ ItemReportController/ItemReport ชุดเดียวกับบทความ

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    DB::table('page_item_view')->delete();
    DB::table('banner_item_click')->delete();

    // ข้อมูลตัวอย่าง banner ขึ้นกับไฟล์รูป (ไม่มีในเทส) — สร้าง 2 หมวดหมู่ + banner หมวดหมู่ละ 1 รายการเอง
    foreach (['A', 'B'] as $name) {
        $category = BannerCategoryInfo::create(['status' => 'Y']);
        DB::table('banner_category_detail')->insert(['id' => $category->id, 'lang' => 'th', 'title' => "หมวด {$name}", 'status' => 'Y']);
        $banner = BannerItemInfo::create(['banner_category_info_id' => $category->id, 'url' => 'https://example.com', 'status' => 'Y']);
        BannerItemDetail::create(['id' => $banner->id, 'lang' => 'th', 'title' => "Banner {$name}", 'status' => 'Y']);
    }
});

/** เพิ่มแถวประวัติตรง ๆ (ข้าม ViewCounter) */
function addItemHit(string $table, string $fk, int $id, string $at, array $extra = []): void
{
    DB::table($table)->insert([
        $fk => $id,
        'lang' => 'th',
        'session_id' => $extra['session_id'] ?? 'sess-'.uniqid(),
        'remote_ip' => $extra['remote_ip'] ?? '10.0.0.1',
        'device_type' => 'mobile',
        'referrer' => $extra['referrer'] ?? null,
        'action_date' => substr($at, 0, 10),
        'status' => 'Y',
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

dataset('modules', [
    'page' => ['page', 'page_item_view', 'page_item_info_id', PageItemInfo::class, 'view_amount', false],
    'banner' => ['banner', 'banner_item_click', 'banner_item_info_id', BannerItemInfo::class, 'click_amount', true],
]);

test('item report requires the module item view permission and aggregates hits', function (string $key, string $table, string $fk, string $model, string $amount, bool $hasCategory) {
    $item = $model::query()->orderBy('id')->firstOrFail();

    actingAsUserWithPermissions([]);
    $this->get(route("admin.{$key}.item.report", $item->id))->assertRedirect(route("admin.{$key}.item.index"));

    actingAsUserWithPermissions(["{$key}.item.view"]);
    addItemHit($table, $fk, $item->id, '2026-09-01 10:00:00', ['session_id' => 'a']);
    addItemHit($table, $fk, $item->id, '2026-09-03 10:00:00', ['session_id' => 'b']);

    $this->get(route("admin.{$key}.item.report", $item->id))->assertOk();
    expect(LogBackAction::where('module_code', "{$key}.item.report")->where('action_type', 'view')->count())->toBe(1);

    $this->get(route("admin.{$key}.item.report", ['item' => $item->id, 'date_from' => '2026-09-01', 'date_to' => '2026-09-03', 'period' => 'day']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Report/Item')
            ->where('module.key', $key)
            ->where('module.metric', $key === 'banner' ? 'click' : 'view')
            ->where('module.has_category', $hasCategory)
            ->where('item.amount', (int) $item->{$amount})
            ->has('series', 3)
            ->where('series.0.views', 1)
            ->where('series.1.views', 0)
            ->where('summary.views', 2)
            ->where('summary.sessions', 2)
            ->etc());

    $csv = $this->get(route("admin.{$key}.item.report.export", ['item' => $item->id, 'date_from' => '2026-09-01', 'date_to' => '2026-09-03']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain($key === 'banner' ? 'ยอดคลิก' : 'ยอดเข้าชม');
})->with('modules');

test('module report menu requires the report permission and renders every tab', function (string $key, string $table, string $fk, string $model, string $amount, bool $hasCategory) {
    $item = $model::query()->orderBy('id')->firstOrFail();

    actingAsUserWithPermissions(["{$key}.item.view"]);
    $this->get(route("admin.{$key}.report.index"))->assertRedirect(route('admin.dashboard'));

    actingAsUserWithPermissions(["{$key}.report.view"]);
    addItemHit($table, $fk, $item->id, now()->format('Y-m-d H:i:s'), ['referrer' => 'http://localhost/th/page/item/1']);

    $this->get(route("admin.{$key}.report.index"))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Report/Index')
            ->where('module.key', $key)
            ->where('can.view_item', false)
            ->where('logs.data.0.item_id', $item->id));

    $tabs = ['overview' => 'Overview', 'top' => 'Top', 'audience' => 'Audience', 'time' => 'Time'] + ($hasCategory ? ['category' => 'Category'] : []);

    foreach ($tabs as $tab => $component) {
        $this->get(route("admin.{$key}.report.{$tab}"))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component("Admin/Report/{$component}")->where('module.key', $key));

        expect(LogBackAction::where('module_code', "{$key}.report.{$tab}")->exists())->toBeTrue();

        $this->get(route("admin.{$key}.report.export", ['tab' => $tab]))->assertOk()->streamedContent();
    }

    // หน้าในเว็บไซต์ที่อ้างอิงมา (banner = หน้าที่มีการคลิก)
    $this->get(route("admin.{$key}.report.audience"))->assertInertia(fn (Assert $page) => $page
        ->where('referrers.paths.0.key', '/th/page/item/1')
        ->where('referrers.paths.0.views', 1)
        ->etc());

    $this->get(route("admin.{$key}.report.top"))->assertInertia(fn (Assert $page) => $page
        ->where('rows.0.id', $item->id)
        ->etc());
})->with('modules');

test('page module has no category tab', function () {
    actingAsUserWithPermissions(['page.report.view']);

    expect(Route::has('admin.page.report.category'))->toBeFalse();

    $this->get(route('admin.page.report.overview'))
        ->assertInertia(fn (Assert $page) => $page->where('categories', null)->where('module.has_category', false)->etc());
});

test('banner overview can be filtered by category', function () {
    actingAsUserWithPermissions(['banner.report.view']);

    $banner = BannerItemInfo::query()->whereNotNull('banner_category_info_id')->orderBy('id')->firstOrFail();
    $other = BannerItemInfo::query()->where('banner_category_info_id', '!=', $banner->banner_category_info_id)->first();

    addItemHit('banner_item_click', 'banner_item_info_id', $banner->id, '2026-09-01 10:00:00');

    if ($other) {
        addItemHit('banner_item_click', 'banner_item_info_id', $other->id, '2026-09-01 10:00:00');
    }

    $this->get(route('admin.banner.report.overview', ['date_from' => '2026-09-01', 'date_to' => '2026-09-01', 'category_id' => $banner->banner_category_info_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.views', 1)
            ->where('filters.category_id', $banner->banner_category_info_id)
            ->etc());
});
