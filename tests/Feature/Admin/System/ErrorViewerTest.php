<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

// หน้า "ตรวจสอบ Error" — อ่านไฟล์ json-error-{front,admin}-*.log (App\Support\Report\ErrorLogReader)

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // ไฟล์ log ของเทสอยู่ในโฟลเดอร์ชั่วคราว — ชี้ channel json (ที่ reader ใช้หาไฟล์) ไปที่นั่น
    $this->logDir = storage_path('framework/testing/error-viewer-'.uniqid());
    File::ensureDirectoryExists($this->logDir);

    foreach (['front', 'admin'] as $side) {
        config([
            "logging.channels.error_{$side}_json.path" => "{$this->logDir}/json-error-{$side}.log",
            "logging.channels.error_{$side}_text.path" => "{$this->logDir}/text-error-{$side}.log",
        ]);
    }
});

afterEach(function () {
    File::deleteDirectory($this->logDir);
});

/** เพิ่ม 1 รายการลงไฟล์ json ของฝั่ง/วันนั้น (รูปแบบเดียวกับ Monolog JsonFormatter) */
function writeErrorLog(string $dir, string $side, string $date, string $time, string $reference, string $message, array $context = []): void
{
    $line = json_encode([
        'message' => $message,
        'context' => ['reference' => $reference, 'url' => "http://localhost/{$side}/x", 'method' => 'GET', 'file' => '/app/Foo.php:10', 'trace' => '#0 {main}'] + $context,
        'level' => 400,
        'level_name' => 'ERROR',
        'channel' => 'testing',
        'datetime' => "{$date}T{$time}.000000+07:00",
        'extra' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    File::append("{$dir}/json-error-{$side}-{$date}.log", $line."\n");
}

test('error viewer requires system.error.view', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.system.errorviewer.index'))->assertRedirect(route('admin.dashboard'));
    $this->getJson(route('admin.system.errorviewer.show', 'ERR-ABCDEFGH'))->assertForbidden();
});

test('lists the newest day first with rows newest first and only dates that have files', function () {
    writeErrorLog($this->logDir, 'front', '2026-09-28', '10:00:00', 'ERR-AAAAAAA2', 'RuntimeException: เก่า');
    writeErrorLog($this->logDir, 'front', '2026-09-29', '09:00:00', 'ERR-BBBBBBB2', 'RuntimeException: เช้า');
    writeErrorLog($this->logDir, 'front', '2026-09-29', '15:00:00', 'ERR-CCCCCCC2', 'App\\Exceptions\\DemoException: บ่าย');
    writeErrorLog($this->logDir, 'admin', '2026-09-27', '08:00:00', 'ERR-DDDDDDD2', 'RuntimeException: หลังบ้าน');

    actingAsUserWithPermissions(['system.error.view']);

    $this->get(route('admin.system.errorviewer.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/System/ErrorViewer/Index')
            ->where('side', 'front')
            ->where('dates', ['2026-09-29', '2026-09-28'])
            ->where('date', '2026-09-29')
            ->where('rows.total', 2)
            ->where('rows.data.0.reference', 'ERR-CCCCCCC2')
            ->where('rows.data.0.class_short', 'DemoException')
            ->where('rows.data.0.message', 'บ่าย')
            ->where('rows.data.1.reference', 'ERR-BBBBBBB2')
            ->where('counts', ['front' => 2, 'admin' => 1]));

    // แท็บหลังบ้านไม่เห็นของหน้าบ้าน
    $this->get(route('admin.system.errorviewer.index', ['side' => 'admin']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dates', ['2026-09-27'])
            ->where('rows.data.0.reference', 'ERR-DDDDDDD2')
            ->has('rows.data', 1));
});

test('filters the selected day by text', function () {
    writeErrorLog($this->logDir, 'front', '2026-09-29', '09:00:00', 'ERR-BBBBBBB2', 'RuntimeException: database timeout');
    writeErrorLog($this->logDir, 'front', '2026-09-29', '10:00:00', 'ERR-CCCCCCC2', 'RuntimeException: other');

    actingAsUserWithPermissions(['system.error.view']);

    $this->get(route('admin.system.errorviewer.index', ['date' => '2026-09-29', 'q' => 'TIMEOUT']))
        ->assertInertia(fn (Assert $page) => $page->where('rows.total', 1)->where('rows.data.0.reference', 'ERR-BBBBBBB2'));
});

test('show finds a reference on either side with the full trace', function () {
    $user = User::where('email', 'admin@mycms.com')->firstOrFail();
    writeErrorLog($this->logDir, 'admin', '2026-09-27', '08:00:00', 'ERR-DDDDDDD2', 'RuntimeException: หลังบ้าน', ['user_id' => $user->id]);

    actingAsUserWithPermissions(['system.error.view']);

    $this->getJson(route('admin.system.errorviewer.show', 'err-ddddddd2'))
        ->assertOk()
        ->assertJson([
            'side' => 'admin',
            'reference' => 'ERR-DDDDDDD2',
            'class' => 'RuntimeException',
            'message' => 'หลังบ้าน',
            'trace' => '#0 {main}',
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

    $this->getJson(route('admin.system.errorviewer.show', 'ERR-ZZZZZZZ2'))->assertNotFound();
    $this->getJson(route('admin.system.errorviewer.show', 'not-a-code'))->assertNotFound();
});

test('invalid side and date input never reach the file system', function () {
    writeErrorLog($this->logDir, 'front', '2026-09-29', '09:00:00', 'ERR-BBBBBBB2', 'RuntimeException: x');

    actingAsUserWithPermissions(['system.error.view']);

    $this->get(route('admin.system.errorviewer.index', ['side' => '../../etc', 'date' => '../../../passwd']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('side', 'front')->where('date', '2026-09-29'));
});

test('a real 500 is written in both formats and found by the viewer', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/admin/test-viewer-500', fn () => throw new RuntimeException('viewer-probe'));

    actingAsUserWithPermissions(['system.error.view']);

    $reference = $this->get('/admin/test-viewer-500')->assertStatus(500)->viewData('page')['props']['reference'];
    $today = now()->toDateString();

    expect(File::exists("{$this->logDir}/text-error-admin-{$today}.log"))->toBeTrue()
        ->and(File::get("{$this->logDir}/text-error-admin-{$today}.log"))->toContain($reference);

    $this->getJson(route('admin.system.errorviewer.show', $reference))
        ->assertOk()
        ->assertJson(['side' => 'admin', 'message' => 'viewer-probe', 'method' => 'GET']);
});
