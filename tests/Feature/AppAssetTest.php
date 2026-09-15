<?php

use App\Models\FileInfo;
use App\Models\SysSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * สร้าง file_info พร้อมไฟล์จริงบน disk (fake) — ใช้เป็นโลโก้/favicon ในเทส
 */
function fakeFileInfo(string $name, string $hashName, string $extension, ?string $mimeType = null): FileInfo
{
    $path = "filemanager/{$hashName}";
    Storage::disk('local')->put($path, 'fake-bytes');

    return FileInfo::create([
        'name' => $name,
        'hash_name' => $hashName,
        'extension' => $extension,
        'mime_type' => $mimeType,
        'file_size' => 10,
        'path' => $path,
        'status' => 'Y',
    ]);
}

// ---------------------------------------------------------------- fallback (ยังไม่ได้ตั้งค่า)

test('logo route falls back to the bundled default logo when not configured', function () {
    $this->get(route('app.logo'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

test('favicon route falls back to the bundled default favicon when not configured', function () {
    $this->get(route('app.favicon'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/x-icon');
});

test('the public asset routes need no login', function () {
    $this->get(route('app.logo'))->assertOk();
    $this->get(route('app.favicon'))->assertOk();
});

// ---------------------------------------------------------------- ตั้งค่าไว้แล้ว

test('logo route serves the configured file and is publicly cacheable', function () {
    $file = fakeFileInfo('logo.png', 'logo-hash.png', 'png', 'image/png');

    SysSetting::create(['group' => 'site', 'name' => 'logo_id', 'value' => (string) $file->id]);

    $response = $this->get(route('app.logo'))->assertOk()->assertHeader('Content-Type', 'image/png');

    expect($response->headers->get('Cache-Control'))->toContain('public');
});

test('favicon route falls back to default when the configured file_info row no longer exists', function () {
    SysSetting::create(['group' => 'site', 'name' => 'favicon_id', 'value' => '99999']);

    $this->get(route('app.favicon'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/x-icon');
});

// ---------------------------------------------------------------- บันทึกผ่านหน้าตั้งค่าระบบ

beforeEach(function () {
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
    ], $overrides);
}

test('saving the site settings with a valid png logo persists logo_id and is served immediately (cache invalidated)', function () {
    actingAsUserWithPermissions(['system.setting.manage']);

    $logo = fakeFileInfo('logo.png', 'l.png', 'png', 'image/png');

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
