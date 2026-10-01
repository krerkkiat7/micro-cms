<?php

namespace Database\Seeders;

use App\Models\ArticleItemDetail;
use App\Models\IntropageItemButton;
use App\Models\IntropageItemDetail;
use App\Models\IntropageItemInfo;
use Database\Seeders\Support\SampleFiles;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Intropage ตัวอย่าง — รูปยินดีต้อนรับ + ข้อความ + ปุ่ม "เข้าสู่เว็บไซต์" (home) และ "แนะนำระบบ" (ลิงก์บทความ)
 * เผยแพร่ตั้งแต่เมื่อวานถึงอีก 2 ปี (ระบบบังคับให้มีวันสิ้นสุดเสมอ). เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class IntropageSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    public function run(): void
    {
        $adminId = $this->adminId();

        $info = IntropageItemInfo::create([
            'background_color' => '#0B1F44',
            'display_type' => 'image',
            'display_size' => 'container_75',
            'image_file_id' => SampleFiles::import('images/intropage/welcome.jpg', 'ตัวอย่าง - Intropage'),
            'detail_font_family' => 'Prompt',
            'detail_font_size' => 20,
            'detail_color' => '#DBEAFE',
            'show_button' => 'Y',
            'button_font_size' => 18,
            'button_font_family' => 'Prompt',
            'publish_date' => now()->subDay()->startOfDay(),
            'publish_down' => now()->addYears(2)->endOfDay(),
            'status' => 'Y',
            'is_temp' => 'Y',
            'created_by' => $adminId,
        ]);

        $this->createDetails(IntropageItemDetail::class, $info->id, fn (string $lang) => [
            'title' => $this->t(['th' => 'ยินดีต้อนรับสู่ MicroCMS', 'en' => 'Welcome to MicroCMS'], $lang),
            'detail' => $this->t([
                'th' => 'เว็บไซต์ตัวอย่างนี้สร้างจากข้อมูลตัวอย่างของ MicroCMS ทั้งหมด ลองสำรวจแล้วเข้าสู่ระบบหลังบ้านเพื่อแก้ไขได้ทันที',
                'en' => 'This website is built entirely from the MicroCMS sample data. Explore it, then sign in to the back office to edit anything.',
            ], $lang),
            'status' => 'Y',
        ]);

        $buttons = [
            ['home', null, ['th' => 'เข้าสู่เว็บไซต์', 'en' => 'Enter website'], '#FFFFFF', '#1E3A8A'],
            [
                'other',
                '/article/item/'.ArticleItemDetail::where('slug', 'introducing-microcms')->value('id').'/introducing-microcms',
                ['th' => 'แนะนำระบบ', 'en' => 'About MicroCMS'],
                '#2563EB',
                '#FFFFFF',
            ],
        ];

        foreach ($buttons as $index => [$type, $url, $texts, $background, $color]) {
            IntropageItemButton::create([
                'intropage_item_info_id' => $info->id,
                'button_type' => $type,
                'sort_order' => $index,
                'button_display_type' => 'text',
                'background_color' => $background,
                'text_color' => $color,
                'url' => $url,
                'link_target' => '_self',
                'texts' => collect($this->languages())->mapWithKeys(fn (string $lang) => [$lang => $this->t($texts, $lang)])->all(),
                'created_by' => $adminId,
            ]);
        }
    }
}
