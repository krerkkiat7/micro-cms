<?php

use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\LogBackAction;
use App\Support\Report\ViewReport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// รายงานการเข้าชมบทความ — รายบทความ (admin.article.item.report*) และเมนูรายงาน (admin.article.report.*)

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    DB::table('article_item_view')->delete();

    $items = ArticleItemInfo::query()->orderBy('id')->take(2)->get();
    $this->article = $items[0];
    $this->other = $items[1];
});

/** เพิ่มแถวการเข้าชมตรง ๆ (ข้าม ViewCounter) */
function addView(int $articleId, string $at, array $extra = []): void
{
    DB::table('article_item_view')->insert([
        'article_item_info_id' => $articleId,
        'lang' => 'th',
        'session_id' => $extra['session_id'] ?? 'sess-'.uniqid(),
        'remote_ip' => $extra['remote_ip'] ?? '10.0.0.1',
        'device_type' => $extra['device_type'] ?? 'desktop',
        'browser' => $extra['browser'] ?? 'Chrome',
        'platform' => $extra['platform'] ?? 'Windows',
        'referrer' => $extra['referrer'] ?? null,
        'action_date' => substr($at, 0, 10),
        'status' => 'Y',
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

// ---------------------------------------------------------------- รายบทความ

test('item report redirects without article.item.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.article.item.report', $this->article->id))
        ->assertRedirect(route('admin.article.item.index'));
});

test('item report aggregates daily views with zero-filled buckets and logs the visit', function () {
    actingAsUserWithPermissions(['article.item.view']);

    addView($this->article->id, '2026-09-01 10:00:00', ['session_id' => 'a']);
    addView($this->article->id, '2026-09-01 11:00:00', ['session_id' => 'a']);
    addView($this->article->id, '2026-09-03 09:00:00', ['session_id' => 'b', 'remote_ip' => '10.0.0.2']);
    addView($this->other->id, '2026-09-02 09:00:00'); // บทความอื่น ไม่นับ

    $this->get(route('admin.article.item.report', $this->article->id))->assertOk();
    expect(LogBackAction::where('module_code', 'article.item.report')->where('action_type', 'view')->count())->toBe(1);

    $this->get(route('admin.article.item.report', [
        'item' => $this->article->id, 'date_from' => '2026-09-01', 'date_to' => '2026-09-03', 'period' => 'day',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Item/Report')
            ->where('filters.period', 'day')
            ->has('series', 3)
            ->where('series.0.views', 2)
            ->where('series.0.sessions', 1)
            ->where('series.1.views', 0)
            ->where('series.2.views', 1)
            ->where('summary.views', 3)
            ->where('summary.sessions', 2)
            ->where('summary.ips', 2)
            ->where('summary.active_days', 2)
            ->where('breakdowns.device_type.0.key', 'desktop')
            ->where('heatmap.1.10', 1) // 2026-09-01 = วันอังคาร (index 1 เมื่อ 0 = จันทร์) เวลา 10:00
            ->etc()
        );
});

test('item report groups by week, month and year', function () {
    actingAsUserWithPermissions(['article.item.view']);

    addView($this->article->id, '2025-12-31 10:00:00'); // พุธ — สัปดาห์เริ่ม 2025-12-29
    addView($this->article->id, '2026-01-04 10:00:00'); // อาทิตย์ — สัปดาห์เดียวกัน
    addView($this->article->id, '2026-01-05 10:00:00'); // จันทร์ — สัปดาห์ใหม่
    addView($this->article->id, '2026-02-10 10:00:00');

    $query = fn (string $period) => route('admin.article.item.report', [
        'item' => $this->article->id, 'date_from' => '2025-12-29', 'date_to' => '2026-02-15', 'period' => $period,
    ]);

    $this->get($query('week'))->assertInertia(fn (Assert $page) => $page
        ->where('series.0.key', '2025-12-29')
        ->where('series.0.views', 2)
        ->where('series.1.key', '2026-01-05')
        ->where('series.1.views', 1)
        ->etc());

    $this->get($query('month'))->assertInertia(fn (Assert $page) => $page
        ->has('series', 3)
        ->where('series.0.key', '2025-12')
        ->where('series.0.views', 1)
        ->where('series.1.views', 2)
        ->where('series.2.views', 1)
        ->etc());

    $this->get($query('year'))->assertInertia(fn (Assert $page) => $page
        ->has('series', 2)
        ->where('series.0.label', '2568')
        ->where('series.1.views', 3)
        ->etc());
});

test('item report compares with the previous period of equal length', function () {
    actingAsUserWithPermissions(['article.item.view']);

    addView($this->article->id, '2026-09-05 10:00:00');
    addView($this->article->id, '2026-09-12 10:00:00');
    addView($this->article->id, '2026-09-13 10:00:00');

    $this->get(route('admin.article.item.report', [
        'item' => $this->article->id, 'date_from' => '2026-09-08', 'date_to' => '2026-09-14',
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('summary.previous.date_from', '2026-09-01')
        ->where('summary.previous.views', 1)
        ->where('summary.change.views', 100)
        ->etc());
});

test('item report exports csv and logs the export', function () {
    actingAsUserWithPermissions(['article.item.view']);
    addView($this->article->id, '2026-09-01 10:00:00');

    $response = $this->get(route('admin.article.item.report.export', [
        'item' => $this->article->id, 'date_from' => '2026-09-01', 'date_to' => '2026-09-02', 'period' => 'day',
    ]));

    $response->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($csv)->toContain('ยอดเข้าชม')
        ->and($csv)->toContain('2026-09-01,2026-09-01,1,1,1')
        ->and(LogBackAction::where('module_code', 'article.item.report')->where('action_type', 'export')->count())->toBe(1);
});

test('filters default to the last 30 days and swap reversed dates', function () {
    $request = request()->duplicate(['date_from' => '2026-09-10', 'date_to' => '2026-09-01']);
    $filters = ViewReport::filters($request);

    expect($filters['date_from'])->toBe('2026-09-01')
        ->and($filters['date_to'])->toBe('2026-09-10')
        ->and($filters['period'])->toBe('day');

    $defaults = ViewReport::filters(request()->duplicate([]));
    expect($defaults['date_to'])->toBe(now()->toDateString())
        ->and($defaults['date_from'])->toBe(now()->subDays(29)->toDateString());
});

// ---------------------------------------------------------------- เมนูรายงาน

test('report menu requires article.report.view', function () {
    actingAsUserWithPermissions(['article.item.view']);

    foreach (['index', 'overview', 'top', 'category', 'audience', 'time'] as $tab) {
        $this->get(route("admin.article.report.{$tab}"))->assertRedirect(route('admin.dashboard'));
    }
});

test('report index lists views with article title and filters by search and date', function () {
    actingAsUserWithPermissions(['article.report.view']);

    $title = ArticleItemDetail::where('id', $this->article->id)->where('lang', 'th')->value('title');
    addView($this->article->id, '2026-09-01 10:00:00', ['remote_ip' => '192.168.1.50']);
    addView($this->other->id, '2026-09-05 10:00:00');

    $this->get(route('admin.article.report.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Report/Index')
            ->has('logs.data', 2)
            ->has('logs.data.0', fn (Assert $row) => $row->hasAll(['id', 'article_id', 'title', 'remote_ip', 'created_at']))
        );

    $this->get(route('admin.article.report.index', ['q' => '192.168.1']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.title', $title));

    $this->get(route('admin.article.report.index', ['date_from' => '2026-09-02']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.article_id', $this->other->id));
});

test('every report tab renders and logs its module code', function () {
    actingAsUserWithPermissions(['article.report.view']);
    addView($this->article->id, now()->format('Y-m-d H:i:s'), ['referrer' => 'https://www.facebook.com/']);

    foreach (['overview' => 'Overview', 'top' => 'Top', 'category' => 'Category', 'audience' => 'Audience', 'time' => 'Time'] as $tab => $component) {
        $this->get(route("admin.article.report.{$tab}"))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component("Admin/Article/Report/{$component}"));

        expect(LogBackAction::where('module_code', "article.report.{$tab}")->where('action_type', 'view')->exists())->toBeTrue();
    }

    $this->get(route('admin.article.report.audience'))->assertInertia(fn (Assert $page) => $page
        ->where('referrers.sources.3.key', 'social')
        ->where('referrers.sources.3.views', 1)
        ->etc());
});

test('top tab ranks articles by views in the date range', function () {
    actingAsUserWithPermissions(['article.report.view']);

    addView($this->article->id, '2026-09-01 10:00:00');
    addView($this->other->id, '2026-09-01 10:00:00');
    addView($this->other->id, '2026-09-02 10:00:00');
    addView($this->article->id, '2026-08-01 10:00:00'); // นอกช่วง

    $this->get(route('admin.article.report.top', ['date_from' => '2026-09-01', 'date_to' => '2026-09-30']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows', 2)
            ->where('rows.0.id', $this->other->id)
            ->where('rows.0.views', 2)
            ->where('rows.0.rank', 1)
            ->where('rows.1.id', $this->article->id)
            ->where('rows.1.views', 1)
            ->etc());
});

test('overview can be filtered by category', function () {
    actingAsUserWithPermissions(['article.report.view']);

    addView($this->article->id, '2026-09-01 10:00:00');
    addView($this->other->id, '2026-09-01 10:00:00');

    $categoryId = $this->article->article_category_info_id;
    $expected = ArticleItemInfo::whereIn('id', [$this->article->id, $this->other->id])
        ->where('article_category_info_id', $categoryId)->count();

    $this->get(route('admin.article.report.overview', ['date_from' => '2026-09-01', 'date_to' => '2026-09-01', 'category_id' => $categoryId]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.category_id', $categoryId)
            ->where('summary.views', $expected)
            ->etc());
});

test('report export returns csv for each tab', function () {
    actingAsUserWithPermissions(['article.report.view']);
    addView($this->article->id, now()->format('Y-m-d H:i:s'));

    foreach (['index', 'overview', 'top', 'category', 'audience', 'time'] as $tab) {
        $response = $this->get(route('admin.article.report.export', ['tab' => $tab]));
        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('text/csv');
        $response->streamedContent();
    }

    expect(LogBackAction::where('action_type', 'export')->count())->toBe(6);
});

test('report pages tell the UI whether article titles may link to the per-article report', function () {
    actingAsUserWithPermissions(['article.report.view']);

    foreach (['index', 'overview', 'top'] as $tab) {
        $this->get(route("admin.article.report.{$tab}"))
            ->assertInertia(fn (Assert $page) => $page->where('can.view_item', false)->etc());
    }

    actingAsUserWithPermissions(['article.report.view', 'article.item.view']);

    $this->get(route('admin.article.report.top'))
        ->assertInertia(fn (Assert $page) => $page->where('can.view_item', true)->etc());
});
