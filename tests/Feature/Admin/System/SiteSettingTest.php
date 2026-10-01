<?php

use App\Models\SysSetting;
use App\Providers\AppServiceProvider;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validSitePayload(array $overrides = []): array
{
    return array_merge([
        'site_name' => 'Test Site',
        'site_email' => '',
        'site_description' => '',
        'logo_id' => null,
        'favicon_id' => null,
        'copyright_year' => '',
        'copyright_owner' => '',
        'lang_selected' => ['th', 'en'],
        'lang_default' => 'th',
        'timezone' => '',
    ], $overrides);
}

// ---------------------------------------------------------------- โลโก้/favicon

test('saving the site settings with a valid png logo persists logo_id and is served immediately (cache invalidated)', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $logo = fakeFileInfo('logo.png', 'l.png', 'png', 'image/png');
    forgetSampleSetting('site', ['logo_id']); // ข้อมูลตัวอย่างตั้งโลโก้ไว้ (ไฟล์ไม่ได้ถูกคัดลอกตอนเทส)

    // เข้าหน้าตั้งค่าครั้งแรก (ยังไม่ตั้งค่า) เพื่อ warm แคชของกลุ่ม 'site' ก่อน — จำลองเคสจริงที่มัก
    // เปิดหน้านี้ก่อนแล้วค่อยบันทึก
    $this->get(route('app.logo'))->assertOk()->assertHeader('Content-Type', 'image/png');

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['logo_id' => $logo->id]))
        ->assertRedirect();

    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'logo_id', 'value' => (string) $logo->id]);

    $this->get(route('app.logo'))->assertOk()->assertHeader('Content-Type', 'image/png');
});

test('logo_id must reference a file with the png extension', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $notPng = fakeFileInfo('logo.jpg', 'l.jpg', 'jpg');

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['logo_id' => $notPng->id]))
        ->assertInvalid(['logo_id']);
});

test('favicon_id must reference a file with the ico extension', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $notIco = fakeFileInfo('favicon.png', 'f.png', 'png');

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['favicon_id' => $notIco->id]))
        ->assertInvalid(['favicon_id']);
});

test('clearing the logo field falls back to the default logo again', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $logo = fakeFileInfo('logo.png', 'l.png', 'png', 'image/png');

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['logo_id' => $logo->id]))->assertRedirect();
    $this->put(route('admin.system.setting.update.site'), validSitePayload(['logo_id' => null]))->assertRedirect();

    expect(SysSetting::query()->where('group', 'site')->where('name', 'logo_id')->value('value'))->toBeNull();
    $this->get(route('app.logo'))->assertOk()->assertHeader('Content-Type', 'image/png');
});

// ---------------------------------------------------------------- ภาษาในระบบ / ภาษาหลัก

test('the database seeder seeds th,en selected and th as the default language', function () {
    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'lang_selected', 'value' => 'th,en']);
    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'lang_default', 'value' => 'th']);
});

test('re-running the database seeder overwrites an existing sys_setting value instead of erroring', function () {
    // sys_setting มี primary key แบบ composite (group, name) ไม่มีคอลัมน์ id ของตัวเอง — seeder ต้องใช้
    // DB::table()->upsert() ตรง ๆ ไม่ใช่ SysSetting::updateOrCreate() (Eloquent) มิฉะนั้นตอน "update" แถวที่มี
    // อยู่แล้วจริง ๆ (ค่าต่างจากเดิม) จะพังด้วย query ที่มี WHERE id = ... ซึ่งไม่มีคอลัมน์นี้อยู่จริง
    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'site_name', 'value' => 'MicroCMS']);

    DB::table('sys_setting')->where('group', 'site')->where('name', 'site_name')->update(['value' => 'Changed Name']);
    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'site_name', 'value' => 'Changed Name']);

    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'site_name', 'value' => 'MicroCMS']);
});

test('saving lang_selected stores it as a single comma-separated record, not one row per language', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload([
        'lang_selected' => ['en', 'th'],
        'lang_default' => 'en',
    ]))->assertRedirect();

    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'lang_selected', 'value' => 'en,th']);
    // ต้องมีแค่ 1 record สำหรับภาษาที่เลือก ไม่แยกเก็บทีละภาษา (เช่น lang_selected_th, lang_selected_en)
    expect(SysSetting::query()->where('group', 'site')->where('name', 'like', 'lang_%')->count())->toBe(2);
    expect(Setting::selectedLanguages())->toBe(['en', 'th']);
    expect(Setting::defaultLanguage())->toBe('en');
});

test('lang_selected requires at least one language', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['lang_selected' => []]))
        ->assertInvalid(['lang_selected']);
});

test('lang_selected only accepts known language codes', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['lang_selected' => ['fr']]))
        ->assertInvalid(['lang_selected.0']);
});

test('lang_default is required', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['lang_default' => '']))
        ->assertInvalid(['lang_default']);
});

test('lang_default must be one of the selected languages', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload([
        'lang_selected' => ['th'],
        'lang_default' => 'en',
    ]))->assertInvalid(['lang_default']);
});

test('Setting::selectedLanguages and defaultLanguage fall back to th,en / th when unset', function () {
    SysSetting::query()->where('group', 'site')->where('name', 'like', 'lang_%')->delete();
    Setting::forget('site');

    expect(Setting::selectedLanguages())->toBe(['th', 'en']);
    expect(Setting::defaultLanguage())->toBe('th');
});

// ---------------------------------------------------------------- โซนเวลา (timezone)

test('saving a valid timezone identifier persists it', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['timezone' => 'Asia/Bangkok']))
        ->assertRedirect();

    $this->assertDatabaseHas('sys_setting', ['group' => 'site', 'name' => 'timezone', 'value' => 'Asia/Bangkok']);
});

test('an unknown timezone identifier is rejected', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['timezone' => 'Asia/Fakeville']))
        ->assertInvalid(['timezone']);
});

test('the timezone field is optional — leaving it empty is stored as null', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['timezone' => '']))
        ->assertSessionHasNoErrors();

    expect(SysSetting::query()->where('group', 'site')->where('name', 'timezone')->value('value'))->toBeNull();
});

test('Setting::timezoneOptions lists every PHP timezone identifier as value/label pairs', function () {
    $options = Setting::timezoneOptions();

    expect($options)->toHaveCount(count(DateTimeZone::listIdentifiers()));
    expect(collect($options)->firstWhere('value', 'Asia/Bangkok'))->toBe(['value' => 'Asia/Bangkok', 'label' => 'Asia/Bangkok']);
});

test('a timezone saved in sys_setting is applied on the next application boot (AppServiceProvider::applyTimezoneSetting)', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $this->put(route('admin.system.setting.update.site'), validSitePayload(['timezone' => 'Pacific/Auckland']))
        ->assertRedirect();

    // จำลอง "boot ครั้งถัดไป" (เช่น request ใหม่) โดยไม่ reboot ทั้งแอป (จะทำให้ SQLite :memory: หลุดข้อมูลที่ seed ไว้) —
    // register() provider เดิมซ้ำบน container ที่ boot แล้วจะเรียก boot() ให้ใหม่ทันที (ดู Application::register())
    $this->app->register(AppServiceProvider::class, force: true);

    expect(config('app.timezone'))->toBe('Pacific/Auckland')
        ->and(date_default_timezone_get())->toBe('Pacific/Auckland');
});
