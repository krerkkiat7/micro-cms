<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// สถิติของประวัติการเข้าสู่ระบบ / การกระทำ (หลังบ้าน) และการใช้งานหน้าบ้าน — ฐาน LogStatsController

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function logRow(string $table, string $at, array $values): void
{
    DB::table($table)->insert($values + [
        'action_date' => substr($at, 0, 10),
        'status' => 'Y',
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

dataset('log stats', [
    'login' => ['system.backlog.login', 'admin.system.backlog.login', ['overview' => 'Overview', 'account' => 'Account', 'security' => 'Security', 'time' => 'Time'], 'Admin/System/BackLogLogin'],
    'action' => ['system.backlog.action', 'admin.system.backlog.action', ['overview' => 'Overview', 'user' => 'User', 'module' => 'Module', 'time' => 'Time'], 'Admin/System/BackLogAction'],
    'front' => ['system.frontlog.access', 'admin.system.frontlog.access', ['overview' => 'Overview', 'page' => 'Page', 'source' => 'Source', 'device' => 'Device', 'time' => 'Time'], 'Admin/System/FrontLogAccess'],
]);

test('stats tabs need the log permission, render and export csv', function (string $permission, string $prefix, array $tabs, string $folder) {
    actingAsUserWithPermissions([]);

    foreach (array_keys($tabs) as $tab) {
        $this->get(route("{$prefix}.{$tab}"))->assertRedirect(route('admin.dashboard'));
    }

    actingAsUserWithPermissions([$permission]);

    $this->get(route("{$prefix}.index"))->assertOk();

    foreach ($tabs as $tab => $component) {
        $this->get(route("{$prefix}.{$tab}"))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component("{$folder}/{$component}"));

        $response = $this->get(route("{$prefix}.export", ['tab' => $tab]));
        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('text/csv');
        $response->streamedContent();
    }

    $this->get(route("{$prefix}.export", ['tab' => 'nope']))->assertNotFound();
})->with('log stats');

test('login stats count results, accounts and suspicious IPs', function () {
    actingAsUserWithPermissions(['system.backlog.login']);
    DB::table('log_back_login')->delete();

    $user = User::factory()->create(['email' => 'staff@example.com']);
    $at = '2026-09-01 09:00:00';
    logRow('log_back_login', $at, ['log_type' => 'login', 'result' => 'success', 'username' => 'staff@example.com', 'user_id' => $user->id, 'remote_ip' => '10.0.0.1']);
    logRow('log_back_login', $at, ['log_type' => 'logout', 'result' => 'success', 'username' => 'staff@example.com', 'user_id' => $user->id, 'remote_ip' => '10.0.0.1']);

    foreach (['a@x.com', 'b@x.com', 'c@x.com'] as $email) {
        logRow('log_back_login', '2026-09-02 03:00:00', ['log_type' => 'login', 'result' => 'fail', 'username' => $email, 'note' => 'ไม่พบบัญชีผู้ใช้งานหลังบ้าน', 'remote_ip' => '203.0.113.9']);
    }
    logRow('log_back_login', '2026-09-02 03:05:00', ['log_type' => 'login', 'result' => 'block', 'username' => 'staff@example.com', 'note' => 'ถูกระงับชั่วคราว', 'remote_ip' => '203.0.113.9']);

    $range = ['date_from' => '2026-09-01', 'date_to' => '2026-09-02', 'period' => 'day'];

    $this->get(route('admin.system.backlog.login.overview', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('counts.success', 1)
            ->where('counts.fail', 3)
            ->where('counts.block', 1)
            ->where('counts.logout', 1)
            ->where('counts.attempts', 5)
            ->where('counts.success_rate', 20)
            ->where('trend.fail', [0, 3])
            ->where('trend.success', [1, 0])
            ->where('reasons.0.key', 'ไม่พบบัญชีผู้ใช้งานหลังบ้าน')
            ->etc());

    $this->get(route('admin.system.backlog.login.security', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ips.0.ip', '203.0.113.9')
            ->where('ips.0.failed', 4)
            ->where('ips.0.usernames', 4)
            ->etc());

    // กรองผู้ใช้งาน = เหตุการณ์ของ user_id + ที่กรอกอีเมลของบัญชีนี้ (รวมตอนถูกบล็อก)
    $this->get(route('admin.system.backlog.login.account', $range + ['user_id' => $user->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('accounts', 1)
            ->where('accounts.0.username', 'staff@example.com')
            ->where('accounts.0.success', 1)
            ->where('accounts.0.block', 1)
            ->where('accounts.0.logout', 1)
            ->etc());

    $this->get(route('admin.system.backlog.login.time', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('successHeatmap.1.9', 1)   // อังคาร 09:00
            ->where('failedHeatmap.2.3', 4)    // พุธ 03:00
            ->etc());
});

test('action stats split types per user, module and record', function () {
    $viewer = actingAsUserWithPermissions(['system.backlog.action']);
    DB::table('log_back_action')->delete();

    $other = User::factory()->create();
    $at = '2026-09-01 10:00:00';
    logRow('log_back_action', $at, ['user_id' => $viewer->id, 'module_code' => 'article.item', 'action_type' => 'create', 'ref_id' => 5, 'value_string' => 'ข่าว A']);
    logRow('log_back_action', $at, ['user_id' => $viewer->id, 'module_code' => 'article.item', 'action_type' => 'update', 'ref_id' => 5, 'value_string' => 'ข่าว A (แก้)']);
    logRow('log_back_action', $at, ['user_id' => $other->id, 'module_code' => 'article.item', 'action_type' => 'view', 'ref_id' => 5, 'value_string' => 'ข่าว A (แก้)']);
    logRow('log_back_action', '2026-09-06 22:00:00', ['user_id' => $other->id, 'module_code' => 'system.user', 'action_type' => 'delete', 'ref_id' => 9, 'value_string' => 'ผู้ใช้ B']);

    $range = ['date_from' => '2026-09-01', 'date_to' => '2026-09-06', 'period' => 'day'];

    $this->get(route('admin.system.backlog.action.overview', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('counts.create', 1)
            ->where('counts.update', 1)
            ->where('counts.view', 1)
            ->where('counts.delete', 1)
            ->where('counts.modules', 2)
            ->where('counts.records', 2)
            ->where('summary.views', 4)
            ->where('summary.sessions', 2)
            ->has('trend.create', 6)
            ->where('trend.delete.5', 1)
            ->etc());

    $this->get(route('admin.system.backlog.action.module', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('modules.0.module', 'article.item')
            ->where('modules.0.total', 3)
            ->where('modules.0.users', 2)
            ->where('records.0.module', 'article.item')
            ->where('records.0.changes', 2)
            ->where('records.0.name', 'ข่าว A (แก้)')
            ->etc());

    $this->get(route('admin.system.backlog.action.user', $range + ['user_id' => $other->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users', 1)
            ->where('users.0.delete', 1)
            ->where('users.0.view', 1)
            ->etc());

    $this->get(route('admin.system.backlog.action.time', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('changeHeatmap.6.22', 1) // อาทิตย์ 22:00 — ลบข้อมูลนอกเวลา
            ->where('heatmap.1.10', 3)
            ->etc());
});

test('front stats exclude bots and compute landing pages, bounce and languages', function () {
    actingAsUserWithPermissions(['system.frontlog.access']);
    DB::table('log_front_access')->delete();

    $at = '2026-09-01 10:00:00';
    $row = fn (string $session, string $title, string $uri, int $offset, array $extra = []) => logRow(
        'log_front_access',
        date('Y-m-d H:i:s', strtotime($at) + $offset),
        $extra + ['session_id' => $session, 'title_name' => $title, 'uri_string' => $uri, 'remote_ip' => '10.0.0.1', 'device_type' => 'mobile',
            'last_visited' => date('Y-m-d H:i:s', strtotime($at) + $offset + 30)],
    );

    $row('s1', 'หน้าแรก', '/th', 0, ['referrer' => 'https://www.google.com/']);
    $row('s1', 'ข่าวสาร', '/th/article/category/1', 60);
    $row('s2', 'ข่าวสาร', '/en/article/category/1', 0);
    $row('bot', 'หน้าแรก', '/th', 0, ['robot' => 'googlebot', 'device_type' => 'robot']);

    $range = ['date_from' => '2026-09-01', 'date_to' => '2026-09-01'];

    $this->get(route('admin.system.frontlog.access.overview', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.views', 3)
            ->where('summary.sessions', 2)
            ->where('robotViews', 1)
            ->where('bounce.sessions', 2)
            ->where('bounce.bounced', 1)
            ->where('bounce.rate', 50)
            ->where('duration.avg_seconds', 30)
            ->etc());

    $this->get(route('admin.system.frontlog.access.page', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('pages.0.title', 'ข่าวสาร')
            ->where('pages.0.views', 2)
            ->has('landing', 2)
            ->etc());

    $this->get(route('admin.system.frontlog.access.source', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('referrers.sources.2.key', 'search')
            ->where('referrers.sources.2.views', 1)
            ->where('languages.0.key', 'th')
            ->where('languages.0.views', 2)
            ->where('languages.1.key', 'en')
            ->etc());

    $this->get(route('admin.system.frontlog.access.device', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('robots.0.key', 'googlebot')
            ->where('breakdowns.device_type.0.key', 'mobile')
            ->where('breakdowns.device_type.0.views', 3)
            ->where('userOptions', null)
            ->etc());
});
