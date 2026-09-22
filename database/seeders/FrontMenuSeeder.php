<?php

namespace Database\Seeders;

use App\Models\ArticleCategoryDetail;
use App\Models\FrontMenuDetail;
use App\Models\FrontMenuInfo;
use App\Models\PageItemDetail;
use App\Support\FrontMenuType;
use App\Support\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างเมนูหน้าบ้าน — ต้องรันหลัง ArticleSeeder และ PageSeeder (อ้าง id ของหมวดหมู่บทความ/หน้าเพจตัวอย่าง)
 * ทำเครื่องหมาย is_temp = 'Y' ทุกแถว เพื่อให้ลบออกได้ภายหลัง (หรือจะใช้ต่อไปก็ได้)
 *
 * โครงตัวอย่าง: เมนูหัวข้อ "บทความ" (heading) มีลูก 3 รายการชี้หมวดหมู่บทความตัวอย่าง (news/activities/articles)
 * + เมนู "หน้าเพจตัวอย่าง" ชี้หน้าเพจตัวอย่างของ PageSeeder และตั้งเป็นหน้าหลัก (is_home)
 *
 * รันเดี่ยว: php artisan db:seed --class=FrontMenuSeeder (ต้องรัน ArticleSeeder/PageSeeder มาก่อนแล้ว)
 */
class FrontMenuSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $languages = Setting::selectedLanguages();
        if ($languages === []) {
            $languages = ['th', 'en'];
        }

        $defaultLang = Setting::defaultLanguage();

        $homeMenuId = $this->upsertMenu(
            $languages,
            $defaultLang,
            name: ['th' => 'หน้าแรก', 'en' => 'Home'],
            attributes: [
                'parent_id' => null,
                'menu_type' => FrontMenuType::PAGE,
                'target_page_item_id' => $this->resolvePageId($defaultLang),
                'is_home' => 'Y',
                'sort_order' => 1,
            ],
        );

        $articleHeadingId = $this->upsertMenu(
            $languages,
            $defaultLang,
            name: ['th' => 'บทความ', 'en' => 'Articles'],
            attributes: [
                'parent_id' => null,
                'menu_type' => FrontMenuType::HEADING,
                'sort_order' => 2,
            ],
        );

        $categories = [
            ['key' => 'news', 'title' => ['th' => 'ข่าวสาร', 'en' => 'News']],
            ['key' => 'activities', 'title' => ['th' => 'กิจกรรม', 'en' => 'Activities']],
            ['key' => 'articles', 'title' => ['th' => 'บทความทั่วไป', 'en' => 'Articles']],
        ];

        foreach ($categories as $index => $category) {
            $categoryId = $this->resolveCategoryId($defaultLang, $category['title'][$defaultLang] ?? $category['title']['th']);

            if ($categoryId === null) {
                continue;
            }

            $this->upsertMenu(
                $languages,
                $defaultLang,
                name: $category['title'],
                attributes: [
                    'parent_id' => $articleHeadingId,
                    'menu_type' => FrontMenuType::ARTICLE_CATEGORY,
                    'target_article_category_id' => $categoryId,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    /**
     * @param  list<string>  $languages
     * @param  array<string, string>  $name
     * @param  array<string, mixed>  $attributes
     */
    private function upsertMenu(array $languages, string $defaultLang, array $name, array $attributes): int
    {
        $defaultName = $name[$defaultLang] ?? $name['th'];

        $existingId = FrontMenuDetail::where('lang', $defaultLang)->where('name', $defaultName)->value('id');

        $info = FrontMenuInfo::updateOrCreate(
            ['id' => $existingId],
            $attributes + ['status' => 'Y', 'is_temp' => 'Y'],
        );

        foreach ($languages as $lang) {
            $values = ['name' => $name[$lang] ?? $name['th'], 'status' => 'Y'];

            $query = FrontMenuDetail::where('id', $info->id)->where('lang', $lang);
            if ($query->exists()) {
                $query->update($values);
            } else {
                FrontMenuDetail::create(['id' => $info->id, 'lang' => $lang] + $values);
            }
        }

        return $info->id;
    }

    private function resolvePageId(string $defaultLang): ?int
    {
        $title = ['th' => 'หน้าเพจตัวอย่าง', 'en' => 'Sample Page'][$defaultLang] ?? 'หน้าเพจตัวอย่าง';

        return PageItemDetail::where('lang', $defaultLang)->where('title', $title)->value('id');
    }

    private function resolveCategoryId(string $defaultLang, string $title): ?int
    {
        return ArticleCategoryDetail::where('lang', $defaultLang)->where('title', $title)->value('id');
    }
}
