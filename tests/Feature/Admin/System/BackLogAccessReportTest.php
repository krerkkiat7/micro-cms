<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// สถิติประวัติการใช้งานหลังบ้าน (admin.system.backlog.access.{overview,user,page,device,time,export})

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

/** เพิ่มแถว log_back_access ตรง ๆ — $seconds = เวลาที่อยู่หน้านั้น (last_visited - created_at) */
function addAccess(?int $userId, string $at, string $title, int $seconds = 60, array $extra = []): void
{
    DB::table('log_back_access')->insert([
        'user_id' => $userId,
        'session_id' => $extra['session_id'] ?? 'sess-'.$userId,
        'title_name' => $title,
        'uri_string' => '/admin/x',
        'remote_ip' => $extra['remote_ip'] ?? '10.0.0.1',
        'device_type' => 'desktop',
        'browser' => 'Chrome',
        'platform' => 'Windows',
        'action_date' => substr($at, 0, 10),
        'last_visited' => date('Y-m-d H:i:s', strtotime($at) + $seconds),
        'status' => 'Y',
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

test('stats tabs require system.backlog.access', function () {
    actingAsUserWithPermissions([]);

    foreach (['overview', 'user', 'page', 'device', 'time'] as $tab) {
        $this->get(route("admin.system.backlog.access.{$tab}"))->assertRedirect(route('admin.dashboard'));
    }
});

test('overview summarises views, users and time on screen', function () {
    $viewer = actingAsUserWithPermissions(['system.backlog.access']);
    DB::table('log_back_access')->delete();

    $other = User::factory()->create();
    addAccess($viewer->id, '2026-09-01 09:00:00', 'Dashboard', 120);
    addAccess($viewer->id, '2026-09-01 09:05:00', 'รายการบทความ', 60);
    addAccess($other->id, '2026-09-02 10:00:00', 'Dashboard', 99999, ['remote_ip' => '10.0.0.1']); // ยาวเกิน cap

    $this->get(route('admin.system.backlog.access.overview', ['date_from' => '2026-09-01', 'date_to' => '2026-09-02', 'period' => 'day']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/BackLogAccess/Overview')
            ->where('summary.views', 3)
            ->where('summary.items', 2)
            ->has('series', 2)
            ->where('series.0.views', 2)
            ->where('duration.total_seconds', 120 + 60 + 1800)
            ->where('users.0.user_id', $viewer->id)
            ->where('users.0.views', 2)
            ->where('users.0.total_seconds', 180)
            ->where('pages.0.title', 'Dashboard')
            ->where('pages.0.users', 2)
            ->has('userOptions')
            ->etc());
});

test('stats can be filtered by user', function () {
    $viewer = actingAsUserWithPermissions(['system.backlog.access']);
    DB::table('log_back_access')->delete();

    $other = User::factory()->create();
    addAccess($viewer->id, '2026-09-01 09:00:00', 'Dashboard');
    addAccess($other->id, '2026-09-01 10:00:00', 'Dashboard');
    addAccess($other->id, '2026-09-01 11:00:00', 'ผู้ใช้งาน');

    $this->get(route('admin.system.backlog.access.page', ['date_from' => '2026-09-01', 'date_to' => '2026-09-01', 'user_id' => $other->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/BackLogAccess/Page')
            ->where('filters.user_id', $other->id)
            ->where('summary.views', 2)
            ->has('pages', 2)
            ->etc());
});

test('device tab counts accounts sharing an IP and time tab builds the heatmap', function () {
    $viewer = actingAsUserWithPermissions(['system.backlog.access']);
    DB::table('log_back_access')->delete();

    $other = User::factory()->create();
    addAccess($viewer->id, '2026-09-01 09:00:00', 'Dashboard', 60, ['remote_ip' => '192.168.1.10']); // อังคาร 09:00
    addAccess($other->id, '2026-09-01 09:30:00', 'Dashboard', 60, ['remote_ip' => '192.168.1.10']);

    $range = ['date_from' => '2026-09-01', 'date_to' => '2026-09-01'];

    $this->get(route('admin.system.backlog.access.device', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ips.0.ip', '192.168.1.10')
            ->where('ips.0.users', 2)
            ->where('breakdowns.browser.0.key', 'Chrome')
            ->etc());

    $this->get(route('admin.system.backlog.access.time', $range))
        ->assertInertia(fn (Assert $page) => $page
            ->where('heatmap.1.9', 2)
            ->etc());
});

test('stats export csv for every tab and the list page shows the stats tabs', function () {
    $viewer = actingAsUserWithPermissions(['system.backlog.access']);
    addAccess($viewer->id, now()->format('Y-m-d H:i:s'), 'Dashboard');

    foreach (['overview', 'user', 'page', 'device', 'time'] as $tab) {
        $this->get(route("admin.system.backlog.access.{$tab}"))->assertOk();

        $response = $this->get(route('admin.system.backlog.access.export', ['tab' => $tab]));
        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('text/csv');
        $response->streamedContent();
    }

    $this->get(route('admin.system.backlog.access.index'))->assertOk();
});
