<?php

namespace Database\Seeders;

use App\Models\ArticleItemDetail;
use App\Support\AppAsset;
use App\Support\FileCache;
use App\Support\Front\FrontCache;
use App\Support\Setting;
use Database\Seeders\Support\SampleFiles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างทั้งชุด (เว็บไซต์ MicroCMS สาธิต) — รูปภาพ/ไฟล์ต้นฉบับอยู่ที่ exampledata/ แล้วนำเข้าโมดูลจัดการไฟล์ของผู้ดูแล
 *
 * ลำดับมีผล: เมนูหน้าบ้านอ้างหน้าเพจ/หมวดหมู่/บทความ, ป้ายโฆษณา/ปุ่ม "อ่านทั้งหมด"/popup อ้างเมนู
 * รันครั้งเดียวต่อฐานข้อมูล — พบข้อมูลตัวอย่างอยู่แล้ว (บทความ introducing-microcms) จะข้ามทั้งชุด
 * ต้องการชุดใหม่: php artisan migrate:fresh --seed (ลบไฟล์เดิมใน storage/app/private/filemanager ด้วยถ้าต้องการ)
 */
class SampleDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public const MARKER_SLUG = 'introducing-microcms';

    public function run(): void
    {
        if (ArticleItemDetail::where('slug', self::MARKER_SLUG)->exists()) {
            $this->command?->warn('ข้ามข้อมูลตัวอย่าง — มีอยู่แล้ว (ล้างแล้วสร้างใหม่ด้วย php artisan migrate:fresh --seed)');

            return;
        }

        SampleFiles::reset();

        $this->call([
            SiteSettingSeeder::class,
            ArticleSeeder::class,
            PageSeeder::class,
            FrontMenuSeeder::class,
            BannerSeeder::class,
            PageLayoutSeeder::class,
            IntropageSeeder::class,
            TemplateSeeder::class,
            ContactusSeeder::class,
            PopupSeeder::class,
        ]);

        $thumbnails = SampleFiles::pregenerateThumbnails();
        if ($thumbnails > 0) {
            $this->command?->info("สร้าง thumbnail ล่วงหน้า {$thumbnails} ไฟล์");
        }

        // seeder ปิด model events → ล้าง cache ที่ปกติล้างอัตโนมัติตอนบันทึก (Redis ไม่ถูกล้างโดย migrate:fresh)
        Setting::forgetAll();
        AppAsset::forgetCache();
        FrontCache::forgetAll();
        FileCache::forgetAll();
    }
}
