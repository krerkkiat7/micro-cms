<?php

namespace Database\Seeders;

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleCategoryInfo;
use App\Support\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างหมวดหมู่บทความ (article_category_info + article_category_detail)
 * ทำเครื่องหมาย is_temp = 'Y' ไว้ทุกแถว เพื่อให้ลบออกได้ภายหลัง (หรือจะใช้ต่อไปก็ได้)
 *
 * รันเดี่ยว: php artisan db:seed --class=ArticleSeeder
 */
class ArticleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $languages = Setting::selectedLanguages();
        if ($languages === []) {
            $languages = ['th', 'en'];
        }

        // หมวดหมู่ตัวอย่าง — key = สำหรับ derive slug ภาษาอังกฤษ, title/intro_text ต่อภาษา (th/en)
        // ภาษาอื่นที่ระบบเปิดใช้นอกเหนือจากนี้ จะ fallback ไปใช้ข้อมูลชุด th
        $categories = [
            [
                'key' => 'news',
                'sort_order' => 1,
                'title' => ['th' => 'ข่าวสาร', 'en' => 'News'],
                'intro_text' => [
                    'th' => 'ข่าวสารและความเคลื่อนไหวล่าสุดขององค์กร',
                    'en' => 'Latest news and updates from the organization',
                ],
            ],
            [
                'key' => 'activities',
                'sort_order' => 2,
                'title' => ['th' => 'กิจกรรม', 'en' => 'Activities'],
                'intro_text' => [
                    'th' => 'กิจกรรมและโครงการต่าง ๆ ที่จัดขึ้น',
                    'en' => 'Activities and projects we have organized',
                ],
            ],
            [
                'key' => 'articles',
                'sort_order' => 3,
                'title' => ['th' => 'บทความทั่วไป', 'en' => 'Articles'],
                'intro_text' => [
                    'th' => 'บทความความรู้และสาระทั่วไป',
                    'en' => 'General knowledge articles',
                ],
            ],
        ];

        foreach ($categories as $category) {
            $info = ArticleCategoryInfo::updateOrCreate(
                ['id' => $this->resolveId($category['key'])],
                [
                    'sort_order' => $category['sort_order'],
                    'status' => 'Y',
                    'is_temp' => 'Y',
                ],
            );

            foreach ($languages as $lang) {
                $title = $category['title'][$lang] ?? $category['title']['th'];
                $introText = $category['intro_text'][$lang] ?? $category['intro_text']['th'];
                $slug = $category['key'].($lang !== $languages[0] ? '-'.$lang : '');

                ArticleCategoryDetail::updateOrCreate(
                    ['id' => $info->id, 'lang' => $lang],
                    [
                        'title' => $title,
                        'intro_text' => $introText,
                        'slug' => $slug,
                        'meta_title' => $title,
                        'meta_description' => $introText,
                        'status' => 'Y',
                    ],
                );
            }
        }
    }

    /**
     * หา id ของหมวดหมู่ตัวอย่างจาก key (เทียบจาก slug ของภาษาแรกที่เคย seed ไว้) เพื่อให้ updateOrCreate
     * อ้างแถวเดิมได้ (ไม่สร้างซ้ำ) โดยไม่ต้องกำหนด id ตายตัว — ถ้ายังไม่เคยมี ให้สร้างแถวใหม่
     */
    private function resolveId(string $key): ?int
    {
        return ArticleCategoryDetail::where('slug', $key)->value('id');
    }
}
