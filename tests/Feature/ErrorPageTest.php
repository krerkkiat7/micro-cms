<?php

use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Monolog\Handler\TestHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

// หน้า error ใหม่ — หน้าบ้าน (แยกภาษา) / หลังบ้าน / หน้าสำรอง Blade + รหัสอ้างอิงใน log (App\Support\ErrorReference)

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    config(['app.debug' => false]);

    // channel error_front / error_admin → เก็บในหน่วยความจำ เพื่อตรวจว่าเขียน log พร้อมรหัสอ้างอิงลงไฟล์ของฝั่งที่ถูกต้อง
    config([
        'logging.channels.error_front' => ['driver' => 'monolog', 'handler' => TestHandler::class],
        'logging.channels.error_admin' => ['driver' => 'monolog', 'handler' => TestHandler::class],
    ]);

    Route::middleware('web')->get('/th/test-error-500', fn () => throw new RuntimeException('secret-db-password'));
    Route::middleware('web')->get('/admin/test-error-500', fn () => throw new RuntimeException('secret-admin-detail'));
    Route::middleware('web')->get('/admin/test-error-403', fn () => abort(403, 'secret-forbidden-reason'));
});

function errorLogRecords(string $channel): array
{
    return Log::channel($channel)->getLogger()->getHandlers()[0]->getRecords();
}

test('front 404 is rendered per language with site branding and a home link', function (string $lang, string $title) {
    $this->get("/{$lang}/no-such-page")
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Front/Error')
            ->where('status', 404)
            ->where('reference', null)
            ->where('homeUrl', route('front.home', ['lang' => $lang]))
            ->where('front.lang', $lang)
            ->where('front.site.name', Setting::siteName())
            ->where('front.t.error_title.404', $title)
            ->missing('front.menu')
            ->missing('front.template'));
})->with([
    'thai' => ['th', 'ไม่พบหน้าที่ต้องการ'],
    'english' => ['en', 'Page not found'],
]);

test('front 500 only says something went wrong and logs a reference', function () {
    $response = $this->get('/th/test-error-500')->assertStatus(500);

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Front/Error')
        ->where('status', 500)
        ->where('reference', fn ($reference) => (bool) preg_match('/^ERR-[2-9A-HJKMNP-Z]{8}$/', $reference)));

    expect($response->getContent())->not->toContain('secret-db-password');

    $reference = $response->viewData('page')['props']['reference'];
    $record = collect(errorLogRecords('error_front'))->first();
    expect(errorLogRecords('error_admin'))->toBe([]);

    expect($record['message'])->toContain('secret-db-password')
        ->and($record['context']['reference'])->toBe($reference)
        ->and($record['context']['url'])->toContain('/th/test-error-500')
        ->and($record['context']['method'])->toBe('GET');
});

test('admin 404 uses the admin error page in Thai', function () {
    $this->get('/admin/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Error')
            ->where('status', 404)
            ->where('title', 'ไม่พบหน้าที่ต้องการ')
            ->where('homeUrl', route('admin.home'))
            ->where('siteName', Setting::siteName()));
});

test('admin errors never show the real reason', function () {
    $forbidden = $this->get('/admin/test-error-403')->assertForbidden();
    $forbidden->assertInertia(fn (Assert $page) => $page->component('Admin/Error')->where('title', 'ไม่มีสิทธิ์เข้าถึงหน้านี้')->where('reference', null));
    expect($forbidden->getContent())->not->toContain('secret-forbidden-reason');

    $server = $this->get('/admin/test-error-500')->assertStatus(500);
    $server->assertInertia(fn (Assert $page) => $page->component('Admin/Error')->where('title', 'เกิดข้อผิดพลาด')->whereType('reference', 'string'));
    expect($server->getContent())->not->toContain('secret-admin-detail');

    // 500 ของหลังบ้านลงไฟล์ error_admin เท่านั้น
    $reference = $server->viewData('page')['props']['reference'];
    expect(collect(errorLogRecords('error_admin'))->pluck('context.reference')->all())->toBe([$reference])
        ->and(errorLogRecords('error_front'))->toBe([]);
});

test('unlisted statuses fall back to the generic 4xx text', function () {
    Route::middleware('web')->get('/th/test-error-418', fn () => abort(418));

    $this->get('/th/test-error-418')
        ->assertStatus(418)
        ->assertInertia(fn (Assert $page) => $page->component('Front/Error')->where('status', 418));
});

test('json requests keep json error responses', function () {
    $this->getJson('/th/no-such-page')->assertNotFound()->assertJsonStructure(['message']);
});

test('non-page paths use the blade fallback page with branding and a home link', function () {
    $response = $this->get('/file/get/no-such-file')->assertNotFound();

    expect($response->getContent())
        ->toContain(Setting::siteName())
        ->toContain('ไม่พบหน้าที่ต้องการ')
        ->toContain('href="'.url('/th').'"');
});

test('blade 5xx fallback shows a reference and no exception detail', function () {
    $html = view('errors.5xx', ['exception' => new HttpException(500, 'secret-fallback-detail')])->render();

    expect($html)->toContain('เกิดข้อผิดพลาด')
        ->toContain('ERR-')
        ->not->toContain('secret-fallback-detail');
});
