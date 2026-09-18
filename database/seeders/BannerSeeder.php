<?php

namespace Database\Seeders;

use App\Models\BannerCategoryDetail;
use App\Models\BannerCategoryInfo;
use App\Support\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างโมดูลป้ายโฆษณา — หมวดหมู่ตัวอย่างเท่านั้น (ทำเครื่องหมาย is_temp = 'Y' ทุกแถว เพื่อให้ลบออกได้
 * ภายหลัง หรือจะใช้ต่อไปก็ได้) ไม่มีป้ายโฆษณาตัวอย่าง เพราะยังไม่มีไฟล์รูปภาพตัวอย่างใน file_info ให้ผูก
 * (โมดูลจัดการไฟล์เป็นพื้นที่ส่วนตัวต่อผู้ใช้ ไม่มี seed ไฟล์กลาง)
 *
 * banner_category_detail ไม่มีคอลัมน์ slug (ต่างจาก article_category_detail) จึง resolve แถวเดิมตอน
 * re-seed จากชื่อ (title) ของภาษาหลักแทน
 *
 * รันเดี่ยว: php artisan db:seed --class=BannerSeeder
 */
class BannerSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $languages = Setting::selectedLanguages();
        if ($languages === []) {
            $languages = ['th', 'en'];
        }

        $defaultLang = Setting::defaultLanguage();

        // หมวดหมู่ตัวอย่าง — ตำแหน่งที่ใช้แสดงผลป้ายโฆษณา, title/intro_text ต่อภาษา (th/en)
        // ภาษาอื่นที่ระบบเปิดใช้นอกเหนือจากนี้ จะ fallback ไปใช้ข้อมูลชุด th
        $categories = [
            [
                'title' => ['th' => 'ไฮไลท์', 'en' => 'Highlight'],
                'intro_text' => [
                    'th' => 'แบนเนอร์ตำแหน่งไฮไลท์หน้าแรก',
                    'en' => 'Highlight banners shown on the homepage',
                ],
            ],
            [
                'title' => ['th' => 'หน่วยงานที่เกี่ยวข้อง', 'en' => 'Related Agencies'],
                'intro_text' => [
                    'th' => 'แบนเนอร์ลิงก์ไปยังหน่วยงานที่เกี่ยวข้อง',
                    'en' => 'Banners linking to related agencies',
                ],
            ],
            [
                'title' => ['th' => 'อื่น ๆ', 'en' => 'Others'],
                'intro_text' => [
                    'th' => 'แบนเนอร์ตำแหน่งอื่น ๆ นอกเหนือจากไฮไลท์และหน่วยงานที่เกี่ยวข้อง',
                    'en' => 'Banners for other positions besides highlight and related agencies',
                ],
            ],
        ];

        foreach ($categories as $category) {
            $defaultTitle = $category['title'][$defaultLang] ?? $category['title']['th'];

            $info = BannerCategoryInfo::updateOrCreate(
                ['id' => $this->resolveId($defaultTitle, $defaultLang)],
                [
                    'status' => 'Y',
                    'is_temp' => 'Y',
                ],
            );

            foreach ($languages as $lang) {
                $title = $category['title'][$lang] ?? $category['title']['th'];
                $introText = $category['intro_text'][$lang] ?? $category['intro_text']['th'];

                BannerCategoryDetail::updateOrCreate(
                    ['id' => $info->id, 'lang' => $lang],
                    [
                        'title' => $title,
                        'intro_text' => $introText,
                        'status' => 'Y',
                    ],
                );
            }
        }
    }

    /**
     * หา id ของหมวดหมู่ตัวอย่างจากชื่อ (title) ของภาษาหลักที่เคย seed ไว้ (banner_category_detail ไม่มีคอลัมน์
     * slug ให้อ้างเหมือน ArticleSeeder) เพื่อให้ updateOrCreate อ้างแถวเดิมได้ (ไม่สร้างซ้ำ) — ถ้ายังไม่เคยมี
     * ให้สร้างแถวใหม่
     */
    private function resolveId(string $defaultTitle, string $defaultLang): ?int
    {
        return BannerCategoryDetail::where('lang', $defaultLang)->where('title', $defaultTitle)->value('id');
    }
}
