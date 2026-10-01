<?php

namespace Database\Seeders;

use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use Database\Seeders\Support\SampleFiles;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างโมดูล Page — หน้า "หน้าแรก" (ข้อมูลทั่วไป + SEO) ส่วนโครงสร้างแถว/คอลัมน์/widget อยู่ที่ PageLayoutSeeder
 * (ต้องสร้างหลังเมนูหน้าบ้าน/ป้ายโฆษณา เพราะปุ่ม "อ่านทั้งหมด" ลิงก์ไปเมนู) เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class PageSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    public const SLUG = 'home';

    public function run(): void
    {
        $info = PageItemInfo::create([
            'intro_image_id' => SampleFiles::import('images/page/home-og.jpg', 'ตัวอย่าง - หน้าเพจ'),
            'background_color' => '#FFFFFF',
            'status' => 'Y',
            'is_temp' => 'Y',
            'created_by' => $this->adminId(),
        ]);

        $title = ['th' => 'หน้าแรก', 'en' => 'Home'];
        $intro = [
            'th' => 'MicroCMS ระบบจัดการเนื้อหาที่ติดตั้งง่าย ใช้งานง่าย — ข่าวสาร คู่มือการใช้งาน และการตั้งค่าบริการภายนอก',
            'en' => 'MicroCMS, the easy-to-install, easy-to-use content management system — news, user guides and external service setup.',
        ];

        $this->createDetails(PageItemDetail::class, $info->id, fn (string $lang) => [
            'title' => $this->t($title, $lang),
            'intro_text' => $this->t($intro, $lang),
            'slug' => self::SLUG,
            'meta_title' => $this->t(['th' => 'MicroCMS — ติดตั้งง่าย ใช้งานง่าย', 'en' => 'MicroCMS — Easy to install, easy to use'], $lang),
            'meta_description' => $this->t($intro, $lang),
            'meta_keywords' => 'MicroCMS, CMS, Laravel',
            'og_title' => 'MicroCMS',
            'og_description' => $this->t($intro, $lang),
            'status' => 'Y',
        ]);
    }

    public static function homeId(): ?int
    {
        return PageItemDetail::where('slug', self::SLUG)->value('id');
    }
}
