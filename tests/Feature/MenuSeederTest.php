<?php

use App\Models\SysAction;
use App\Models\SysMenu;
use App\Models\SysMenuGroup;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MenuSeeder;

test('menu seeder creates groups and menus', function () {
    $this->seed(MenuSeeder::class);

    expect(SysMenuGroup::count())->toBeGreaterThan(0);
    expect(SysMenu::count())->toBeGreaterThan(0);

    $system = SysMenuGroup::with('menus')->find('system');
    expect($system)->not->toBeNull();
    expect($system->menus)->not->toBeEmpty();
    expect($system->menus->first()->group->id)->toBe('system');
});

test('menu seeder is idempotent', function () {
    $this->seed(MenuSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(SysMenu::whereKey('system-user')->count())->toBe(1);
});

test('every menu action_code matches a seeded sys_action code', function () {
    $this->seed(DatabaseSeeder::class);

    $codes = SysAction::pluck('code');
    $orphans = SysMenu::whereNotNull('action_code')
        ->whereNotIn('action_code', $codes)
        ->pluck('action_code');

    expect($orphans)->toBeEmpty("action_code ที่ไม่มีใน sys_action: {$orphans->implode(', ')}");
});

test('database seeder is idempotent', function () {
    $this->seed(DatabaseSeeder::class);
    $afterFirst = SysAction::count();

    $this->seed(DatabaseSeeder::class);

    expect(SysAction::count())->toBe($afterFirst);
    expect(SysMenu::count())->toBeGreaterThan(0);
    expect(UserGroup::where('name', 'Super Admin')->count())->toBe(1);
    expect(User::where('email', 'admin@microcms.com')->count())->toBe(1);
});
