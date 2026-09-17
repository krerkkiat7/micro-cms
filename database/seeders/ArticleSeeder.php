<?php

namespace Database\Seeders;

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\ArticleItemPart;
use App\Models\ArticleItemPartDetail;
use App\Models\ArticleTagDetail;
use App\Models\ArticleTagInfo;
use App\Support\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลตัวอย่างโมดูลบทความ (ตั้งค่าโมดูล + หมวดหมู่ + แท็ก + บทความ) หมวดหมู่/แท็ก/บทความทำเครื่องหมาย
 * is_temp = 'Y' ไว้ทุกแถว เพื่อให้ลบออกได้ภายหลัง (หรือจะใช้ต่อไปก็ได้) บทความตัวอย่างมีแค่ part ประเภทข้อความ
 * เพราะยังไม่มีไฟล์ตัวอย่างใน file_info ให้ผูก (โมดูลจัดการไฟล์เป็นพื้นที่ส่วนตัวต่อผู้ใช้ ไม่มี seed ไฟล์กลาง)
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

        // ตั้งค่าโมดูลบทความ (sys_setting group = article) — ดู Admin\Article\ArticleSettingController
        // ใช้ DB::table()->upsert() ตรง ๆ ไม่ใช่ SysSetting::updateOrCreate() — sys_setting มี primary key
        // แบบ composite (group, name) ไม่มีคอลัมน์ id เอง Eloquent ที่ไม่รู้จัก key นี้จะพัง (WHERE id = ...)
        // ทันทีที่ต้อง UPDATE แถวที่มีอยู่แล้วจริง ๆ (ต่างจากตอน insert ใหม่ที่ไม่มีปัญหา จึงไม่เคยเจอตอนเทส)
        $settings = [
            ['group' => 'article', 'name' => 'list_per_page', 'value' => '10'],
            ['group' => 'article', 'name' => 'list_display_mode', 'value' => 'card'],
        ];

        DB::table('sys_setting')->upsert(
            array_map(fn (array $s) => $s + ['created_at' => now(), 'updated_at' => now()], $settings),
            ['group', 'name'],
            ['value', 'updated_at'],
        );

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

        // แท็กตัวอย่าง
        $tags = [
            ['key' => 'announcement', 'name' => ['th' => 'ประชาสัมพันธ์', 'en' => 'Announcement']],
            ['key' => 'event', 'name' => ['th' => 'กิจกรรม', 'en' => 'Event']],
            ['key' => 'knowledge', 'name' => ['th' => 'ความรู้', 'en' => 'Knowledge']],
            ['key' => 'update', 'name' => ['th' => 'อัปเดต', 'en' => 'Update']],
        ];

        $tagIds = [];

        foreach ($tags as $tag) {
            $tagInfo = ArticleTagInfo::updateOrCreate(
                ['id' => $this->resolveTagId($tag['key'])],
                [
                    'status' => 'Y',
                    'is_temp' => 'Y',
                ],
            );

            foreach ($languages as $lang) {
                $name = $tag['name'][$lang] ?? $tag['name']['th'];
                $slug = $tag['key'].($lang !== $languages[0] ? '-'.$lang : '');

                ArticleTagDetail::updateOrCreate(
                    ['id' => $tagInfo->id, 'lang' => $lang],
                    [
                        'name' => $name,
                        'slug' => $slug,
                        'status' => 'Y',
                    ],
                );
            }

            $tagIds[$tag['key']] = $tagInfo->id;
        }

        // บทความตัวอย่าง — key = สำหรับ derive slug ภาษาอังกฤษ, category = key ของหมวดหมู่ด้านบน,
        // tags = key ของแท็กด้านบนที่จะผูกกับบทความนี้
        $articles = [
            [
                'key' => 'welcome-website',
                'category' => 'news',
                'tags' => ['announcement'],
                'publish_days_ago' => 5,
                'title' => ['th' => 'เปิดตัวเว็บไซต์ใหม่', 'en' => 'Launching Our New Website'],
                'intro_text' => [
                    'th' => 'ยินดีต้อนรับสู่เว็บไซต์ใหม่ของเรา ออกแบบมาให้ใช้งานง่ายและรวดเร็วยิ่งขึ้น',
                    'en' => 'Welcome to our brand-new website, redesigned to be easier and faster to use.',
                ],
                'detail' => [
                    'th' => 'เราภูมิใจนำเสนอเว็บไซต์เวอร์ชันใหม่ที่ปรับปรุงทั้งหน้าตาและประสบการณ์การใช้งาน '
                        .'พร้อมเนื้อหาข่าวสารและกิจกรรมที่จะอัปเดตอย่างสม่ำเสมอ',
                    'en' => 'We are proud to present our redesigned website with an improved look and feel, '
                        .'along with news and activity updates published regularly.',
                ],
            ],
            [
                'key' => 'annual-meeting-2026',
                'category' => 'activities',
                'tags' => ['event'],
                'publish_days_ago' => 3,
                'title' => ['th' => 'ประชุมใหญ่สามัญประจำปี 2569', 'en' => 'Annual General Meeting 2026'],
                'intro_text' => [
                    'th' => 'กำหนดการประชุมใหญ่สามัญประจำปี พร้อมวาระสำคัญที่สมาชิกไม่ควรพลาด',
                    'en' => 'Schedule for this year\'s annual general meeting, with important agenda items.',
                ],
                'detail' => [
                    'th' => 'ขอเชิญสมาชิกทุกท่านเข้าร่วมประชุมใหญ่สามัญประจำปี เพื่อรับฟังรายงานผลการดำเนินงาน '
                        .'และร่วมพิจารณาวาระสำคัญต่าง ๆ ขององค์กร',
                    'en' => 'All members are invited to attend the annual general meeting to review the year\'s '
                        .'performance report and discuss key organizational agenda items.',
                ],
            ],
            [
                'key' => 'workshop-recap',
                'category' => 'activities',
                'tags' => ['event', 'knowledge'],
                'publish_days_ago' => 10,
                'title' => ['th' => 'สรุปกิจกรรมอบรมเชิงปฏิบัติการ', 'en' => 'Workshop Recap'],
                'intro_text' => [
                    'th' => 'รวมภาพบรรยากาศและสิ่งที่ได้เรียนรู้จากกิจกรรมอบรมเชิงปฏิบัติการที่ผ่านมา',
                    'en' => 'A recap of the highlights and key takeaways from our recent hands-on workshop.',
                ],
                'detail' => [
                    'th' => 'กิจกรรมอบรมเชิงปฏิบัติการที่ผ่านมาได้รับความสนใจจากผู้เข้าร่วมเป็นอย่างมาก '
                        .'ทีมงานขอขอบคุณทุกท่านที่ร่วมกิจกรรมและหวังว่าจะได้พบกันอีกในครั้งถัดไป',
                    'en' => 'Our recent hands-on workshop received great engagement from participants. '
                        .'Thank you to everyone who joined, and we look forward to seeing you at the next one.',
                ],
            ],
            [
                'key' => 'productivity-tips',
                'category' => 'articles',
                'tags' => ['knowledge'],
                'publish_days_ago' => 15,
                'title' => [
                    'th' => '5 เคล็ดลับการทำงานอย่างมีประสิทธิภาพ',
                    'en' => '5 Tips for Working More Efficiently',
                ],
                'intro_text' => [
                    'th' => 'รวมเคล็ดลับง่าย ๆ ที่ช่วยให้การทำงานในแต่ละวันมีประสิทธิภาพมากขึ้น',
                    'en' => 'A collection of simple tips to help make your daily work more efficient.',
                ],
                'detail' => [
                    'th' => 'ตั้งแต่การจัดลำดับความสำคัญของงาน ไปจนถึงการพักสมองระหว่างวัน '
                        .'บทความนี้รวบรวมเคล็ดลับที่นำไปปรับใช้ได้จริงในชีวิตการทำงานประจำวัน',
                    'en' => 'From prioritizing tasks to taking mindful breaks throughout the day, this article '
                        .'gathers practical tips you can apply to your everyday work routine.',
                ],
            ],
            [
                'key' => 'system-update',
                'category' => 'news',
                'tags' => ['update'],
                'publish_days_ago' => 1,
                'title' => ['th' => 'อัปเดตระบบเวอร์ชันล่าสุด', 'en' => 'Latest System Update'],
                'intro_text' => [
                    'th' => 'รายละเอียดฟีเจอร์ใหม่และการปรับปรุงในเวอร์ชันล่าสุดของระบบ',
                    'en' => 'Details on new features and improvements in the latest system release.',
                ],
                'detail' => [
                    'th' => 'เวอร์ชันล่าสุดมาพร้อมการปรับปรุงประสิทธิภาพและแก้ไขปัญหาที่ผู้ใช้งานแจ้งเข้ามา '
                        .'ขอบคุณทุกท่านที่ช่วยแจ้งข้อเสนอแนะเพื่อพัฒนาระบบให้ดียิ่งขึ้น',
                    'en' => 'The latest release includes performance improvements and fixes reported by our '
                        .'users. Thank you for your feedback that helps us keep improving the system.',
                ],
            ],
        ];

        foreach ($articles as $article) {
            $categoryId = ArticleCategoryDetail::where('slug', $article['category'])->value('id');

            $itemInfo = ArticleItemInfo::updateOrCreate(
                ['id' => $this->resolveItemId($article['key'])],
                [
                    'article_category_info_id' => $categoryId,
                    'publish_date' => now()->subDays($article['publish_days_ago']),
                    'status' => 'Y',
                    'is_temp' => 'Y',
                ],
            );

            foreach ($languages as $lang) {
                $title = $article['title'][$lang] ?? $article['title']['th'];
                $introText = $article['intro_text'][$lang] ?? $article['intro_text']['th'];
                $slug = $article['key'].($lang !== $languages[0] ? '-'.$lang : '');

                ArticleItemDetail::updateOrCreate(
                    ['id' => $itemInfo->id, 'lang' => $lang],
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

            // เนื้อหาเริ่มต้นด้วย part ข้อความเสมอ
            $part = ArticleItemPart::updateOrCreate(
                ['id' => $this->resolvePartId($itemInfo->id)],
                [
                    'article_item_info_id' => $itemInfo->id,
                    'sort_order' => 0,
                    'part_type' => 'text',
                ],
            );

            foreach ($languages as $lang) {
                $detail = $article['detail'][$lang] ?? $article['detail']['th'];

                ArticleItemPartDetail::updateOrCreate(
                    ['id' => $part->id, 'lang' => $lang],
                    ['detail' => $detail],
                );
            }

            $itemInfo->tags()->syncWithoutDetaching(
                array_map(fn (string $key) => $tagIds[$key], $article['tags']),
            );
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

    /**
     * หา id ของแท็กตัวอย่างจาก key (เทียบจาก slug) — เทียบเคียง resolveId()
     */
    private function resolveTagId(string $key): ?int
    {
        return ArticleTagDetail::where('slug', $key)->value('id');
    }

    /**
     * หา id ของบทความตัวอย่างจาก key (เทียบจาก slug ของภาษาแรกที่เคย seed ไว้) — เทียบเคียง resolveId()
     */
    private function resolveItemId(string $key): ?int
    {
        return ArticleItemDetail::where('slug', $key)->value('id');
    }

    /**
     * หา id ของ part ข้อความแรก (sort_order = 0) ของบทความที่ระบุ — ใช้ตอน re-seed ให้ updateOrCreate
     * อ้างแถวเดิมได้แทนที่จะสร้าง part ซ้ำ
     */
    private function resolvePartId(int $itemId): ?int
    {
        return ArticleItemPart::where('article_item_info_id', $itemId)->where('sort_order', 0)->value('id');
    }
}
