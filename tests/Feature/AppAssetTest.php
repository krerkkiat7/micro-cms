<?php

use App\Models\SysSetting;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

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

// ---------------------------------------------------------------- shared prop appLogoUrl (frontend AppLogo.vue)

test('the shared appLogoUrl prop is null when no logo is configured — frontend keeps the default icon', function () {
    $this->get(route('front.home', ['lang' => 'th']))
        ->assertInertia(fn (Assert $page) => $page->where('appLogoUrl', null));
});

test('the shared appLogoUrl prop points at the app.logo route once a logo is configured', function () {
    $file = fakeFileInfo('logo.png', 'shared-logo.png', 'png', 'image/png');
    SysSetting::create(['group' => 'site', 'name' => 'logo_id', 'value' => (string) $file->id]);

    $this->get(route('front.home', ['lang' => 'th']))
        ->assertInertia(fn (Assert $page) => $page->where('appLogoUrl', route('app.logo')));
});
