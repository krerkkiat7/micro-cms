<?php

use App\Models\FrontMenuDetail;
use App\Support\Setting;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    // FrontMenuSeeder สร้างเมนูตัวอย่างพร้อมชื่อทั้งภาษาไทยและอังกฤษ
    $this->seed(DatabaseSeeder::class);
});

test('menu tree nodes carry the default-language name for the list and reorder dialog', function () {
    actingAsUserWithPermissions(['system.menu.view']);

    $tree = $this->get(route('admin.system.menu.index'))
        ->assertOk()
        ->viewData('page')['props']['tree'];

    $defaultLang = Setting::defaultLanguage();

    $walk = function (array $nodes) use (&$walk, $defaultLang) {
        foreach ($nodes as $node) {
            $expected = FrontMenuDetail::where('id', $node['id'])->where('lang', $defaultLang)->value('name');

            expect($node['name'])->toBe($expected);
            $walk($node['children']);
        }
    };

    expect($tree)->not->toBeEmpty();
    $walk($tree);
    // ตัวอย่างมีชื่อภาษาไทย (ภาษาหลัก) — ไม่ใช่ชื่อภาษาอังกฤษ
    expect(collect($tree)->pluck('name'))->toContain('หน้าแรก');
});
