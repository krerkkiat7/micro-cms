<?php

use App\Models\SysMenu;
use App\Models\SysMenuGroup;
use Database\Seeders\MenuSeeder;

test('menu seeder creates groups and menus', function () {
    $this->seed(MenuSeeder::class);

    expect(SysMenuGroup::count())->toBeGreaterThan(0);
    expect(SysMenu::count())->toBeGreaterThan(0);

    $content = SysMenuGroup::with('menus')->find('content');
    expect($content)->not->toBeNull();
    expect($content->menus)->not->toBeEmpty();
    expect($content->menus->first()->group->id)->toBe('content');
});

test('menu seeder is idempotent', function () {
    $this->seed(MenuSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(SysMenu::whereKey('system-user')->count())->toBe(1);
});
