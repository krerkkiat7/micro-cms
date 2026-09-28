<?php

use App\Models\LogBackAction;
use App\Support\Front\FrontCache;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('index redirects to dashboard without banner.setting.manage', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.banner.setting.index'))
        ->assertRedirect(route('admin.dashboard'));
});

test('index renders the clear cache page for a user with banner.setting.manage', function () {
    actingAsUserWithPermissions(['banner.setting.manage']);

    $this->get(route('admin.banner.setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Banner/Setting/Index'));
});

test('clear cache actions require banner.setting.manage', function () {
    actingAsUserWithPermissions([]);

    foreach (['setting', 'front', 'all'] as $name) {
        $this->post(route("admin.banner.setting.clearcache.{$name}"))->assertRedirect(route('admin.dashboard'));
    }
});

test('clear cache actions bump the front cache version and log the action', function () {
    actingAsUserWithPermissions(['banner.setting.manage']);

    foreach (['setting', 'front', 'all'] as $name) {
        $before = FrontCache::version();
        $this->post(route("admin.banner.setting.clearcache.{$name}"))->assertSessionHas('success');
        expect(FrontCache::version())->not->toBe($before);
    }

    expect(LogBackAction::where('module_code', 'banner.setting.cache')->count())->toBe(3);
});
