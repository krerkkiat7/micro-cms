<?php

namespace Database\Seeders;

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleItemDetail;
use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;
use App\Support\FrontMenuType;
use Database\Seeders\Support\SampleFiles;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างเมนูหน้าบ้าน — ต้องรันหลัง ArticleSeeder/PageSeeder (อ้าง id หมวดหมู่/บทความ/หน้าเพจ)
 *
 *   หน้าแรก (page "หน้าแรก", is_home) / แนะนำระบบ (บทความ introducing-microcms) /
 *   บทความ (heading) → ข่าวสาร, การใช้งานระบบ, การตั้งค่าบริการภายนอก (หมวดหมู่บทความ) / ติดต่อเรา
 *
 * เมนูเนื้อหามีรูปส่วนหัว + หัวเรื่อง/หัวเรื่องรองสองภาษา. เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class FrontMenuSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    /** @var array<string, int> key → front_menu_info.id (ให้ seeder อื่นอ้างผ่าน FrontMenuSeeder::id()) */
    private static array $ids = [];

    public function run(): void
    {
        self::$ids = [];

        $this->menu('home', ['th' => 'หน้าแรก', 'en' => 'Home'], [
            'menu_type' => FrontMenuType::PAGE,
            'target_page_item_id' => PageSeeder::homeId(),
            'is_home' => 'Y',
            'show_title' => 'N',
            'show_subtitle' => 'N',
            'show_breadcrumb' => 'N',
        ]);

        $this->menu('about', ['th' => 'แนะนำระบบ', 'en' => 'About'], [
            'menu_type' => FrontMenuType::ARTICLE_ITEM,
            'target_article_item_id' => ArticleItemDetail::where('slug', 'introducing-microcms')->value('id'),
        ], header: [
            'image' => 'images/menu/header-introducing.jpg',
            'title' => ['th' => 'แนะนำระบบ', 'en' => 'About MicroCMS'],
            'subtitle' => ['th' => 'ระบบจัดการเนื้อหาที่ติดตั้งง่าย ใช้งานง่าย', 'en' => 'An easy-to-install, easy-to-use content management system'],
        ]);

        $heading = $this->menu('articles', ['th' => 'บทความ', 'en' => 'Articles'], [
            'menu_type' => FrontMenuType::HEADING,
        ]);

        $categories = [
            'news' => [
                'name' => ['th' => 'ข่าวสาร', 'en' => 'News'],
                'subtitle' => ['th' => 'ข่าวประชาสัมพันธ์และความเคลื่อนไหวล่าสุด', 'en' => 'Announcements and the latest updates'],
            ],
            'user-guide' => [
                'name' => ['th' => 'การใช้งานระบบ', 'en' => 'User Guide'],
                'subtitle' => ['th' => 'คู่มือการใช้งานแต่ละโมดูลทีละขั้นตอน', 'en' => 'Step-by-step guides to each module'],
            ],
            'external-services' => [
                'name' => ['th' => 'การตั้งค่าบริการภายนอก', 'en' => 'External Services'],
                'subtitle' => ['th' => 'Turnstile, Google Maps, Google Analytics และ SMTP', 'en' => 'Turnstile, Google Maps, Google Analytics and SMTP'],
            ],
        ];

        foreach ($categories as $slug => $category) {
            $this->menu($slug, $category['name'], [
                'parent_id' => $heading,
                'menu_type' => FrontMenuType::ARTICLE_CATEGORY,
                'target_article_category_id' => ArticleCategoryDetail::where('slug', $slug)->value('id'),
            ], header: [
                'image' => "images/menu/header-{$slug}.jpg",
                'title' => $category['name'],
                'subtitle' => $category['subtitle'],
            ]);
        }

        $this->menu('contactus', ['th' => 'ติดต่อเรา', 'en' => 'Contact Us'], [
            'menu_type' => FrontMenuType::CONTACTUS,
        ], header: [
            'image' => 'images/menu/header-contactus.jpg',
            'title' => ['th' => 'ติดต่อเรา', 'en' => 'Contact Us'],
            'subtitle' => ['th' => 'สอบถามข้อมูลหรือส่งข้อเสนอแนะถึงทีมงาน', 'en' => 'Questions or feedback? Get in touch with the team'],
        ]);
    }

    /** id ของเมนูตัวอย่างตาม key (home, about, articles, news, user-guide, external-services, contactus) */
    public static function id(string $key): ?int
    {
        return self::$ids[$key] ?? null;
    }

    /**
     * @param  array<string, string>  $name
     * @param  array<string, mixed>  $attributes
     * @param  array{image: string, title: array<string, string>, subtitle: array<string, string>}|null  $header
     */
    private function menu(string $key, array $name, array $attributes, ?array $header = null): int
    {
        $siblings = FrontMenuInfo::where('parent_id', $attributes['parent_id'] ?? null)->count();

        $headerAttributes = $header === null ? [] : [
            'show_header_image' => 'Y',
            'header_image_id' => SampleFiles::import($header['image'], 'ตัวอย่าง - เมนู'),
            'header_image_aspect_ratio' => 'natural',
            'header_image_fit' => 'cover',
            'header_image_background' => '#1E3A8A',
            'show_title' => 'Y',
            'title_font_size' => 36,
            'title_font_family' => 'Prompt',
            'title_color' => '#FFFFFF',
            'title_bold' => 'Y',
            'show_subtitle' => 'Y',
            'subtitle_font_size' => 18,
            'subtitle_font_family' => 'Sarabun',
            'subtitle_color' => '#DBEAFE',
            'subtitle_bold' => 'N',
            'header_content_align' => 'left',
            'use_container' => 'Y',
            'show_breadcrumb' => 'Y',
        ];

        $info = FrontMenuInfo::create($attributes + $headerAttributes + [
            'parent_id' => null,
            'link_target' => '_self',
            'is_home' => 'N',
            'sort_order' => $siblings + 1,
            'status' => 'Y',
            'is_temp' => 'Y',
            'created_by' => $this->adminId(),
        ]);

        $this->createDetails(FrontMenuDetail::class, $info->id, fn (string $lang) => [
            'name' => $this->t($name, $lang),
            'title' => $header !== null ? $this->t($header['title'], $lang) : null,
            'subtitle' => $header !== null ? $this->t($header['subtitle'], $lang) : null,
            'status' => 'Y',
        ]);

        return self::$ids[$key] = $info->id;
    }
}
