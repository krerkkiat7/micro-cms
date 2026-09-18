<?php

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

test('index renders the placeholder page for a user with banner.setting.manage', function () {
    actingAsUserWithPermissions(['banner.setting.manage']);

    $this->get(route('admin.banner.setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Banner/Setting/Index'));
});
