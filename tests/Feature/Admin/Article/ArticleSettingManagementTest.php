<?php

use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\Setting;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // seed สร้างค่าเริ่มต้นของกลุ่ม article มาด้วย (ดู ArticleSeeder) — เทสด้านล่างจึงเห็นค่า default ตั้งแต่แรก
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validArticleSettingPayload(array $overrides = []): array
{
    return array_merge([
        'list_per_page' => '10',
        'list_display_mode' => 'card',
    ], $overrides);
}

// ---------------------------------------------------------------- index

test('index redirects to dashboard without article.setting.manage', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.article.setting.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders the seeded defaults', function () {
    actingAsUserWithPermissions(['article.setting.manage']);

    $this->get(route('admin.article.setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Article/Setting/Index')
            ->where('settings.list_per_page', '10')
            ->where('settings.list_display_mode', 'card')
        );
});

test('re-running ArticleSeeder overwrites a changed setting value instead of erroring', function () {
    // sys_setting มี primary key แบบ composite (group, name) ไม่มีคอลัมน์ id ของตัวเอง — ต้อง seed ด้วย
    // DB::table()->upsert() ตรง ๆ ไม่ใช่ SysSetting::updateOrCreate() (Eloquent) มิฉะนั้นตอน "update" แถวที่มี
    // อยู่แล้วจริง ๆ (ค่าต่างจากเดิม) จะพังด้วย query ที่มี WHERE id = ... ซึ่งไม่มีคอลัมน์นี้อยู่จริง
    DB::table('sys_setting')->where('group', 'article')->where('name', 'list_per_page')->update(['value' => '99']);
    $this->assertDatabaseHas('sys_setting', ['group' => 'article', 'name' => 'list_per_page', 'value' => '99']);

    $this->seed(ArticleSeeder::class);

    $this->assertDatabaseHas('sys_setting', ['group' => 'article', 'name' => 'list_per_page', 'value' => '10']);
});

// ---------------------------------------------------------------- update

test('update redirects to dashboard without article.setting.manage', function () {
    actingAsUserWithPermissions([]);

    $this->put(route('admin.article.setting.update'), validArticleSettingPayload())
        ->assertRedirect(route('admin.dashboard'));
});

test('update requires list_per_page to be an integer between 1 and 100', function () {
    actingAsUserWithPermissions(['article.setting.manage']);

    $this->put(route('admin.article.setting.update'), validArticleSettingPayload(['list_per_page' => '0']))
        ->assertInvalid(['list_per_page']);

    $this->put(route('admin.article.setting.update'), validArticleSettingPayload(['list_per_page' => '101']))
        ->assertInvalid(['list_per_page']);
});

test('update only accepts card or row for list_display_mode', function () {
    actingAsUserWithPermissions(['article.setting.manage']);

    $this->put(route('admin.article.setting.update'), validArticleSettingPayload(['list_display_mode' => 'grid']))
        ->assertInvalid(['list_display_mode']);
});

test('update persists the settings, invalidates the cache, and logs the action', function () {
    $me = actingAsUserWithPermissions(['article.setting.manage']);

    // warm แคชของกลุ่ม article ก่อน ให้เหมือนเคสจริงที่มักเปิดหน้าอื่นที่อ่านค่านี้ไปแล้วก่อนมาบันทึก
    Setting::group('article');

    $this->put(route('admin.article.setting.update'), validArticleSettingPayload([
        'list_per_page' => '25',
        'list_display_mode' => 'row',
    ]))->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('sys_setting', ['group' => 'article', 'name' => 'list_per_page', 'value' => '25']);
    $this->assertDatabaseHas('sys_setting', ['group' => 'article', 'name' => 'list_display_mode', 'value' => 'row']);

    expect(Setting::get('article', 'list_per_page'))->toBe('25')
        ->and(Setting::get('article', 'list_display_mode'))->toBe('row');

    expect(SysSetting::query()->where('group', 'article')->where('name', 'list_per_page')->value('created_by'))
        ->toBe($me->id);

    expect(LogBackAction::where('module_code', 'article.setting')
        ->where('action_type', 'update')
        ->exists())->toBeTrue();
});

// ---------------------------------------------------------------- clear cache

test('clearcache page redirects to dashboard without article.setting.manage', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.article.setting.clearcache'))
        ->assertRedirect(route('admin.dashboard'));
});

test('clearcache page renders for a user with article.setting.manage', function () {
    actingAsUserWithPermissions(['article.setting.manage']);

    $this->get(route('admin.article.setting.clearcache'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Article/Setting/ClearCache'));
});

test('clearCacheSetting clears the article settings cache and logs the action', function () {
    actingAsUserWithPermissions(['article.setting.manage']);

    Setting::group('article'); // warm แคชก่อน
    expect(Cache::has('sys_setting.article'))->toBeTrue();

    $this->post(route('admin.article.setting.clearcache.setting'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Cache::has('sys_setting.article'))->toBeFalse();

    expect(LogBackAction::where('module_code', 'article.setting.cache')
        ->where('action_type', 'clear')
        ->exists())->toBeTrue();
});

test('clearCacheAll clears the article settings cache and logs the action', function () {
    actingAsUserWithPermissions(['article.setting.manage']);

    Setting::group('article');
    expect(Cache::has('sys_setting.article'))->toBeTrue();

    $this->post(route('admin.article.setting.clearcache.all'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Cache::has('sys_setting.article'))->toBeFalse();
});

// ---------------------------------------------------------------- integration กับตั้งค่าระบบ

test('the system settings page does not expose the article group in its own settings prop', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->get(route('admin.system.setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('settings.site')
            ->has('settings.smtp')
            ->has('settings.turnstile')
            ->has('settings.login_back')
            ->missing('settings.article')
        );
});

test('the system settings "clear cache by group" endpoint can clear the article group too', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    Setting::group('article');
    expect(Cache::has('sys_setting.article'))->toBeTrue();

    $this->post(route('admin.system.setting.clearcache.group', 'article'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Cache::has('sys_setting.article'))->toBeFalse();
});

test('the system settings "clear all" endpoint also clears the article settings cache', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    Setting::group('site');
    Setting::group('article');
    expect(Cache::has('sys_setting.site'))->toBeTrue();
    expect(Cache::has('sys_setting.article'))->toBeTrue();

    $this->post(route('admin.system.setting.clearcache.all'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Cache::has('sys_setting.site'))->toBeFalse();
    expect(Cache::has('sys_setting.article'))->toBeFalse();
});
