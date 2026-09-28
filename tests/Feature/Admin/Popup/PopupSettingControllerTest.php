<?php

use App\Models\LogBackAction;
use App\Models\SysSetting;
use App\Support\Front\FrontCache;
use App\Support\PopupSetting;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('setting pages redirect to dashboard without popup.setting.manage', function () {
    actingAsUserWithPermissions([]);

    $this->get(route('admin.popup.setting.index'))->assertRedirect(route('admin.dashboard'));
    $this->get(route('admin.popup.setting.clearcache'))->assertRedirect(route('admin.dashboard'));
    $this->put(route('admin.popup.setting.update'), ['display_order' => 'sort_asc'])->assertRedirect(route('admin.dashboard'));

    foreach (['setting', 'front', 'all'] as $name) {
        $this->post(route("admin.popup.setting.clearcache.{$name}"))->assertRedirect(route('admin.dashboard'));
    }
});

test('index renders the default display order', function () {
    actingAsUserWithPermissions(['popup.setting.manage']);

    $this->get(route('admin.popup.setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Popup/Setting/Index')
            ->where('settings.display_order', 'publish_desc')
        );
});

test('update saves the display order and logs it', function () {
    actingAsUserWithPermissions(['popup.setting.manage']);

    $this->put(route('admin.popup.setting.update'), ['display_order' => 'sort_asc'])->assertSessionHas('success');

    expect(SysSetting::where('group', 'popup')->where('name', 'display_order')->value('value'))->toBe('sort_asc')
        ->and(PopupSetting::all()['display_order'])->toBe('sort_asc')
        ->and(PopupSetting::orderBy())->toBe(['sort_order', 'asc'])
        ->and(LogBackAction::where('module_code', 'popup.setting')->where('action_type', 'update')->exists())->toBeTrue();
});

test('update rejects an unknown display order', function () {
    actingAsUserWithPermissions(['popup.setting.manage']);

    $this->put(route('admin.popup.setting.update'), ['display_order' => 'random'])->assertSessionHasErrors('display_order');
});

test('clear cache page renders and clear actions bump the front cache version', function () {
    actingAsUserWithPermissions(['popup.setting.manage']);

    $this->get(route('admin.popup.setting.clearcache'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Popup/Setting/ClearCache'));

    foreach (['setting', 'front', 'all'] as $name) {
        $before = FrontCache::version();
        $this->post(route("admin.popup.setting.clearcache.{$name}"))->assertSessionHas('success');
        expect(FrontCache::version())->not->toBe($before);
    }

    expect(LogBackAction::where('module_code', 'popup.setting')->where('action_type', 'clear')->count())->toBe(3);
});
