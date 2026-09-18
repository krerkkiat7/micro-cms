<?php

namespace Database\Seeders;

use App\Models\IntropageItemButton;
use App\Models\IntropageItemDetail;
use App\Models\IntropageItemInfo;
use App\Support\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างโมดูล Intropage — ทำเครื่องหมาย is_temp = 'Y' เพื่อให้ลบออกได้ภายหลัง (หรือจะใช้ต่อไปก็ได้)
 * ไม่มีตารางหมวดหมู่ให้ seed (intropage ไม่มี category tier) จึงสร้างแค่ Intropage ตัวอย่าง 1 รายการ พร้อมปุ่ม
 * "เข้าหน้าแรก" เริ่มต้น 1 ปุ่ม — ใช้ display_type = youtubeurl (ไม่ต้องพึ่งไฟล์ใน file_info ให้ผูก เหมือนที่
 * BannerSeeder ข้ามป้ายโฆษณาตัวอย่างเพราะไม่มีไฟล์ผูก)
 *
 * รันเดี่ยว: php artisan db:seed --class=IntropageSeeder
 */
class IntropageSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $languages = Setting::selectedLanguages();
        if ($languages === []) {
            $languages = ['th', 'en'];
        }

        $defaultLang = Setting::defaultLanguage();

        $title = ['th' => 'หน้าต้อนรับตัวอย่าง', 'en' => 'Sample Welcome Page'];
        $detail = [
            'th' => 'ยินดีต้อนรับเข้าสู่เว็บไซต์ของเรา',
            'en' => 'Welcome to our website',
        ];
        $buttonText = ['th' => 'เข้าสู่เว็บไซต์', 'en' => 'Enter Site'];

        $defaultTitle = $title[$defaultLang] ?? $title['th'];

        $info = IntropageItemInfo::updateOrCreate(
            ['id' => $this->resolveId($defaultTitle, $defaultLang)],
            [
                'display_type' => 'youtubeurl',
                'display_size' => 'screen_100',
                'vdo_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'show_button' => 'Y',
                'publish_date' => now(),
                'publish_down' => now()->addDays(30),
                'status' => 'Y',
                'is_temp' => 'Y',
            ],
        );

        foreach ($languages as $lang) {
            IntropageItemDetail::updateOrCreate(
                ['id' => $info->id, 'lang' => $lang],
                [
                    'title' => $title[$lang] ?? $title['th'],
                    'detail' => $detail[$lang] ?? $detail['th'],
                    'status' => 'Y',
                ],
            );
        }

        if ($info->buttons()->count() === 0) {
            $texts = [];
            foreach ($languages as $lang) {
                $texts[$lang] = $buttonText[$lang] ?? $buttonText['th'];
            }

            IntropageItemButton::create([
                'intropage_item_info_id' => $info->id,
                'button_type' => 'home',
                'sort_order' => 0,
                'button_display_type' => 'text',
                'background_color' => '#465fff',
                'text_color' => '#ffffff',
                'texts' => $texts,
            ]);
        }
    }

    /**
     * หา id ของ Intropage ตัวอย่างจากชื่อ (title) ของภาษาหลักที่เคย seed ไว้ (intropage_item_detail ไม่มี
     * คอลัมน์ slug ให้อ้างเหมือน ArticleSeeder) เพื่อให้ updateOrCreate อ้างแถวเดิมได้ (ไม่สร้างซ้ำ) — ถ้ายังไม่
     * เคยมี ให้สร้างแถวใหม่
     */
    private function resolveId(string $defaultTitle, string $defaultLang): ?int
    {
        return IntropageItemDetail::where('lang', $defaultLang)->where('title', $defaultTitle)->value('id');
    }
}
