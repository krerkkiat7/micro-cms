<?php

namespace Database\Seeders;

use App\Models\BannerCategoryDetail;
use App\Models\BannerCategoryInfo;
use App\Models\BannerItemDetail;
use App\Models\BannerItemInfo;
use Database\Seeders\Support\SampleFiles;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างโมดูลป้ายโฆษณา — หมวด Highlight + ป้ายโฆษณา 4 รายการ (รูป 21:9) ลิงก์ไปเมนูหน้าบ้าน
 * ต้องรันหลัง FrontMenuSeeder. เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class BannerSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    public const HIGHLIGHT_TITLE = 'Highlight';

    public function run(): void
    {
        $category = BannerCategoryInfo::create(['status' => 'Y', 'is_temp' => 'Y', 'created_by' => $this->adminId()]);

        $this->createDetails(BannerCategoryDetail::class, $category->id, fn (string $lang) => [
            'title' => self::HIGHLIGHT_TITLE,
            'intro_text' => $this->t([
                'th' => 'ป้ายโฆษณาหลักที่แสดงเป็นสไลด์ด้านบนของหน้าแรก',
                'en' => 'Main banners shown as the slideshow at the top of the home page',
            ], $lang),
            'status' => 'Y',
        ]);

        $banners = [
            [
                'image' => 'images/banner/banner-welcome.jpg',
                'menu' => 'about',
                'title' => ['th' => 'ยินดีต้อนรับสู่ MicroCMS', 'en' => 'Welcome to MicroCMS'],
                'intro' => ['th' => 'ระบบจัดการเนื้อหาที่ติดตั้งง่าย ใช้งานง่าย สำหรับเว็บไซต์องค์กร', 'en' => 'An easy-to-install, easy-to-use content management system for organization websites'],
            ],
            [
                'image' => 'images/banner/banner-news.jpg',
                'menu' => 'news',
                'title' => ['th' => 'ข่าวสารล่าสุด', 'en' => 'Latest News'],
                'intro' => ['th' => 'ติดตามฟีเจอร์ใหม่และความเคลื่อนไหวของ MicroCMS', 'en' => 'Keep up with new features and MicroCMS updates'],
            ],
            [
                'image' => 'images/banner/banner-user-guide.jpg',
                'menu' => 'user-guide',
                'title' => ['th' => 'คู่มือการใช้งานระบบ', 'en' => 'User Guide'],
                'intro' => ['th' => 'เรียนรู้การจัดการบทความ หน้าเพจ ป้ายโฆษณา และ Popup ทีละขั้นตอน', 'en' => 'Learn to manage articles, pages, banners and popups step by step'],
            ],
            [
                'image' => 'images/banner/banner-external-services.jpg',
                'menu' => 'external-services',
                'title' => ['th' => 'เชื่อมต่อบริการภายนอก', 'en' => 'Connect External Services'],
                'intro' => ['th' => 'ตั้งค่า Turnstile, Google Maps, Google Analytics และ SMTP', 'en' => 'Set up Turnstile, Google Maps, Google Analytics and SMTP'],
            ],
        ];

        foreach ($banners as $index => $banner) {
            $item = BannerItemInfo::create([
                'banner_category_info_id' => $category->id,
                'intro_image_id' => SampleFiles::import($banner['image'], 'ตัวอย่าง - ป้ายโฆษณา'),
                'link_type' => 'menu',
                'front_menu_info_id' => FrontMenuSeeder::id($banner['menu']),
                'link_target' => '_self',
                'publish_date' => now()->subDays(30)->startOfDay(),
                'click_amount' => [42, 27, 19, 11][$index],
                'sort_order' => $index + 1,
                'status' => 'Y',
                'is_temp' => 'Y',
                'created_by' => $this->adminId(),
            ]);

            $this->createDetails(BannerItemDetail::class, $item->id, fn (string $lang) => [
                'title' => $this->t($banner['title'], $lang),
                'intro_text' => $this->t($banner['intro'], $lang),
                'status' => 'Y',
            ]);
        }
    }

    public static function highlightId(): ?int
    {
        return BannerCategoryDetail::where('title', self::HIGHLIGHT_TITLE)->value('id');
    }
}
