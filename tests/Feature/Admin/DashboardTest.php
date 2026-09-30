<?php

use App\Models\ArticleItemInfo;
use App\Models\LogBackAccess;
use App\Models\LogBackAction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Dashboard หลังบ้าน — แต่ละส่วนคำนวณ/ส่งเฉพาะเมื่อผู้ใช้มีสิทธิ์ของส่วนนั้น (App\Support\Report\DashboardReport)

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    DB::table('article_item_view')->delete();
    DB::table('contactus_item')->delete();
});

function addDashboardContact(string $status, string $at): void
{
    DB::table('contactus_item')->insert([
        'fullname' => 'ผู้ติดต่อ',
        'subject' => "หัวข้อ {$status}",
        'lang' => 'th',
        'process_status' => $status,
        'status' => 'Y',
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

function addDashboardArticleView(int $articleId, string $at): void
{
    DB::table('article_item_view')->insert([
        'article_item_info_id' => $articleId,
        'lang' => 'th',
        'session_id' => 'sess-'.uniqid(),
        'remote_ip' => '10.0.0.1',
        'action_date' => substr($at, 0, 10),
        'status' => 'Y',
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

test('user without permissions sees the dashboard with no data sections', function () {
    actingAsUserWithPermissions([]);
    addDashboardContact('unread', now()->toDateTimeString());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('shortcuts', [])
            ->where('dashboard.attention', [])
            ->where('dashboard.kpis', [])
            ->where('dashboard.trend', null)
            ->where('dashboard.topArticles', null)
            ->where('dashboard.recentContacts', null)
            ->where('dashboard.content', [])
            ->where('dashboard.recentActions', null));

    expect(LogBackAccess::where('title_name', 'แดชบอร์ด')->count())->toBe(1);
});

test('contactus permission only shows the contactus sections', function () {
    actingAsUserWithPermissions(['contactus.item.view']);
    addDashboardContact('unread', now()->toDateTimeString());
    addDashboardContact('unread', now()->subDay()->toDateTimeString());
    addDashboardContact('considering', now()->subDays(2)->toDateTimeString());
    addDashboardContact('done', now()->subDays(10)->toDateTimeString());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dashboard.attention', 2)
            ->where('dashboard.attention.0.key', 'contactus_unread')
            ->where('dashboard.attention.0.count', 2)
            ->where('dashboard.attention.1.key', 'contactus_considering')
            ->where('dashboard.attention.1.count', 1)
            ->has('dashboard.kpis', 1)
            ->where('dashboard.kpis.0.key', 'contactus')
            ->where('dashboard.kpis.0.value', 3)
            ->where('dashboard.kpis.0.change', 200) // 7 วันก่อนหน้ามี 1 รายการ (10 วันก่อน) → 3 เทียบ 1 = +200%
            ->has('dashboard.recentContacts.items', 4)
            ->where('dashboard.trend', null)
            ->where('dashboard.topArticles', null)
            ->where('dashboard.content', [])
            ->where('dashboard.recentActions', null));
});

test('article report permission shows views, trend and top articles without edit links', function () {
    actingAsUserWithPermissions(['article.report.view']);
    $article = ArticleItemInfo::query()->orderBy('id')->firstOrFail();

    addDashboardArticleView($article->id, now()->toDateTimeString());
    addDashboardArticleView($article->id, now()->subDays(2)->toDateTimeString());
    addDashboardArticleView($article->id, now()->subDays(20)->toDateTimeString()); // อยู่ในกราฟ 30 วัน แต่ไม่อยู่ในการ์ด 7 วัน

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dashboard.kpis', 1)
            ->where('dashboard.kpis.0.key', 'article')
            ->where('dashboard.kpis.0.value', 2)
            ->has('dashboard.trend.labels', 30)
            ->has('dashboard.trend.datasets', 1)
            ->where('dashboard.trend.datasets.0.key', 'article')
            ->where('dashboard.trend.datasets.0.data', fn ($data) => array_sum($data->all()) === 3)
            ->has('dashboard.topArticles.items', 1)
            ->where('dashboard.topArticles.items.0.views', 2)
            ->where('dashboard.topArticles.items.0.href', null) // ไม่มี article.item.view → ไม่มีลิงก์แก้ไข
            ->where('dashboard.content', [])
            ->where('dashboard.recentContacts', null));
});

test('article item permission shows content overview, expiring articles and edit links', function () {
    actingAsUserWithPermissions(['article.item.view', 'article.item.manage', 'article.report.view']);
    $article = ArticleItemInfo::query()->orderBy('id')->firstOrFail();
    $article->forceFill(['status' => 'Y', 'publish_date' => null, 'publish_down' => now()->addDays(3)])->save();
    addDashboardArticleView($article->id, now()->toDateTimeString());

    $published = ArticleItemInfo::query()
        ->where('status', 'Y')
        ->where(fn ($q) => $q->whereNull('publish_date')->orWhere('publish_date', '<=', now()))
        ->where(fn ($q) => $q->whereNull('publish_down')->orWhere('publish_down', '>', now()))
        ->count();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('shortcuts', 1)
            ->where('shortcuts.0.key', 'article')
            ->where('dashboard.attention.0.key', 'article_expiring')
            ->where('dashboard.attention.0.count', 1)
            ->has('dashboard.content', 1)
            ->where('dashboard.content.0.key', 'article')
            ->where('dashboard.content.0.total', ArticleItemInfo::count())
            ->where('dashboard.content.0.published', $published)
            ->where('dashboard.topArticles.items.0.href', route('admin.article.item.edit', $article->id)));
});

test('super admin sees every section', function () {
    $this->actingAs(User::where('email', 'admin@admin.com')->firstOrFail());
    LogBackAction::record('system.user', 'update', 'ทดสอบ', 1);
    DB::table('log_back_login')->insert([
        'log_type' => 'login', 'result' => 'fail', 'username' => 'x@example.com', 'action_date' => now()->toDateString(),
        'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('shortcuts', 3)
            ->where('dashboard.attention', fn ($items) => collect($items)->contains('key', 'login_failed'))
            ->has('dashboard.kpis', 5)
            ->has('dashboard.trend.datasets', 4)
            ->has('dashboard.topArticles')
            ->has('dashboard.recentContacts')
            ->has('dashboard.content', 5)
            ->where('dashboard.recentActions.items.0.value_string', 'ทดสอบ'));
});
