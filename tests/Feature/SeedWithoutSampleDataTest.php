<?php

use App\Models\ArticleItemInfo;
use App\Models\FileInfo;
use App\Models\SysMenu;
use App\Models\SysTemplate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

/**
 * ติดตั้งระบบเปล่า: SEED_SAMPLE_DATA=false php artisan migrate --seed — seed เฉพาะข้อมูลระบบ ไม่มีข้อมูลตัวอย่าง
 */
test('SEED_SAMPLE_DATA=false seeds only the system data (permissions, admin, back-office menu)', function () {
    putenv('SEED_SAMPLE_DATA=false');

    try {
        $this->seed(DatabaseSeeder::class);
    } finally {
        putenv('SEED_SAMPLE_DATA');
    }

    expect(User::where('email', 'admin@microcms.com')->exists())->toBeTrue()
        ->and(SysMenu::count())->toBeGreaterThan(0)
        ->and(ArticleItemInfo::count())->toBe(0)
        ->and(FileInfo::count())->toBe(0)
        ->and(SysTemplate::count())->toBe(0);

    // หน้าบ้านยังเปิดได้แม้ไม่มี template/เมนู (ใช้ค่าเริ่มต้นของ template)
    $this->get('/th/contactus')->assertOk();
});
