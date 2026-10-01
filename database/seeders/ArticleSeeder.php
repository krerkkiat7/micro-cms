<?php

namespace Database\Seeders;

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleCategoryInfo;
use App\Models\ArticleItemDetail;
use App\Models\ArticleItemInfo;
use App\Models\ArticleItemPart;
use App\Models\ArticleItemPartDetail;
use App\Models\ArticleItemPartFile;
use App\Models\ArticleTagDetail;
use App\Models\ArticleTagInfo;
use App\Support\ArticleSetting;
use Database\Seeders\Support\SampleFiles;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ข้อมูลตัวอย่างโมดูลบทความ — ตั้งค่าโมดูล + หมวดหมู่ 4 หมวด + แท็ก + บทความ 15 เรื่อง (เนื้อหาใน data/articles.php)
 * ทุกแถว is_temp = 'Y' (แสดงที่หน้าบ้านตามปกติ — แก้ไขต่อหรือลบออกได้). เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class ArticleSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    private const FOLDER = 'ตัวอย่าง - บทความ';

    /** หมวดหมู่ — key = slug */
    private const CATEGORIES = [
        'news' => [
            'cover' => 'images/article/cat-news.jpg',
            'title' => ['th' => 'ข่าวสาร', 'en' => 'News'],
            'intro' => ['th' => 'ข่าวประชาสัมพันธ์และความเคลื่อนไหวล่าสุดของ MicroCMS', 'en' => 'Announcements and the latest updates from MicroCMS'],
            'detail' => [
                'th' => '<p>ติดตามการเปิดตัวฟีเจอร์ใหม่ เคล็ดลับการใช้งาน และแผนพัฒนาของ MicroCMS ได้ที่นี่</p>',
                'en' => '<p>Follow new feature releases, tips and the MicroCMS roadmap here.</p>',
            ],
        ],
        'user-guide' => [
            'cover' => 'images/article/cat-user-guide.jpg',
            'title' => ['th' => 'การใช้งานระบบ', 'en' => 'User Guide'],
            'intro' => ['th' => 'คู่มือการใช้งานแต่ละโมดูลของ MicroCMS ทีละขั้นตอน', 'en' => 'Step-by-step guides to each MicroCMS module'],
            'detail' => [
                'th' => '<p>เรียนรู้การจัดการบทความ หน้าเพจ Intropage ป้ายโฆษณา และ Popup พร้อมภาพหน้าจอจากระบบจริง</p>',
                'en' => '<p>Learn how to manage articles, pages, the intro page, banners and popups, with screenshots from the real system.</p>',
            ],
        ],
        'external-services' => [
            'cover' => 'images/article/cat-external-services.jpg',
            'title' => ['th' => 'การตั้งค่าบริการภายนอก', 'en' => 'External Services'],
            'intro' => ['th' => 'เชื่อมต่อ MicroCMS กับ Cloudflare Turnstile, Google Maps, Google Analytics และ SMTP', 'en' => 'Connect MicroCMS to Cloudflare Turnstile, Google Maps, Google Analytics and SMTP'],
            'detail' => [
                'th' => '<p>บริการเหล่านี้ไม่บังคับ แต่ช่วยให้เว็บไซต์ปลอดภัยและใช้งานได้ครบขึ้น ตั้งค่าทั้งหมดได้ที่ <strong>จัดการระบบ → ตั้งค่าระบบ</strong></p>',
                'en' => '<p>These services are optional but make your website safer and more complete. Configure them all in <strong>System → Settings</strong>.</p>',
            ],
        ],
        'general' => [
            'cover' => 'images/article/cat-general.jpg',
            'title' => ['th' => 'ทั่วไป', 'en' => 'General'],
            'intro' => ['th' => 'เรื่องทั่วไปเกี่ยวกับ MicroCMS', 'en' => 'General information about MicroCMS'],
            'detail' => ['th' => '', 'en' => ''],
        ],
    ];

    /** แท็ก — key = slug */
    private const TAGS = [
        'microcms' => ['th' => 'MicroCMS', 'en' => 'MicroCMS'],
        'announcement' => ['th' => 'ประชาสัมพันธ์', 'en' => 'Announcement'],
        'release' => ['th' => 'อัปเดตเวอร์ชัน', 'en' => 'Release'],
        'security' => ['th' => 'ความปลอดภัย', 'en' => 'Security'],
        'seo' => ['th' => 'SEO', 'en' => 'SEO'],
        'language' => ['th' => 'หลายภาษา', 'en' => 'Multilingual'],
        'guide' => ['th' => 'คู่มือ', 'en' => 'Guide'],
        'getting-started' => ['th' => 'เริ่มต้นใช้งาน', 'en' => 'Getting Started'],
        'article' => ['th' => 'บทความ', 'en' => 'Articles'],
        'page' => ['th' => 'หน้าเพจ', 'en' => 'Pages'],
        'intropage' => ['th' => 'Intropage', 'en' => 'Intro Page'],
        'banner' => ['th' => 'ป้ายโฆษณา', 'en' => 'Banners'],
        'popup' => ['th' => 'Popup', 'en' => 'Popups'],
        'cloudflare' => ['th' => 'Cloudflare', 'en' => 'Cloudflare'],
        'turnstile' => ['th' => 'Turnstile', 'en' => 'Turnstile'],
        'google' => ['th' => 'Google', 'en' => 'Google'],
        'google-maps' => ['th' => 'Google Maps', 'en' => 'Google Maps'],
        'google-analytics' => ['th' => 'Google Analytics', 'en' => 'Google Analytics'],
        'smtp' => ['th' => 'SMTP', 'en' => 'SMTP'],
        'gmail' => ['th' => 'Gmail', 'en' => 'Gmail'],
    ];

    /** ค่าเริ่มต้นของ setting ในแต่ละประเภท part (เหมือน resources/js/utils/articleParts.ts) */
    private const PART_SETTINGS = [
        'text' => [],
        'image' => ['alignment' => 'center', 'size' => 'large', 'show_caption' => true],
        'images' => ['columns' => '3', 'autoplay' => false, 'interval_ms' => '4000'],
        'documents' => [],
    ];

    public function run(): void
    {
        $this->seedSettings();
        $categoryIds = $this->seedCategories();
        $tagIds = $this->seedTags();

        foreach (require __DIR__.'/data/articles.php' as $article) {
            $this->seedArticle($article, $categoryIds, $tagIds);
        }
    }

    /**
     * ตั้งค่าโมดูลบทความ (sys_setting group = article) — ค่าเริ่มต้นจาก ArticleSetting::defaults() แล้วปรับให้เห็นความสามารถ
     * (upsert ตรง ๆ — sys_setting เป็น composite PK ใช้ Eloquent update ไม่ได้)
     */
    private function seedSettings(): void
    {
        $values = array_merge(ArticleSetting::defaults(), [
            'list_show_category_intro' => 'Y',
            'list_show_category_detail' => 'Y',
            'list_per_page' => '9',
            'list_display_mode' => 'card',
            'card_title_lines' => '2',
            'detail_show_cover' => 'Y',
            'detail_show_print' => 'Y',
            'detail_share_position' => 'bottom',
        ]);

        DB::table('sys_setting')->upsert(
            collect($values)->map(fn ($value, $name) => [
                'group' => 'article',
                'name' => $name,
                'value' => (string) $value,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all(),
            ['group', 'name'],
            ['value', 'updated_at'],
        );
    }

    /**
     * @return array<string, int> slug → id
     */
    private function seedCategories(): array
    {
        $ids = [];
        $order = 0;

        foreach (self::CATEGORIES as $slug => $category) {
            $info = ArticleCategoryInfo::create([
                'intro_image_id' => SampleFiles::import($category['cover'], self::FOLDER),
                'sort_order' => ++$order,
                'status' => 'Y',
                'is_temp' => 'Y',
                'created_by' => $this->adminId(),
            ]);

            $this->createDetails(ArticleCategoryDetail::class, $info->id, fn (string $lang) => [
                'title' => $this->t($category['title'], $lang),
                'intro_text' => $this->t($category['intro'], $lang),
                'detail' => $this->t($category['detail'], $lang) ?: null,
                'slug' => $slug,
                'meta_title' => $this->t($category['title'], $lang),
                'meta_description' => $this->t($category['intro'], $lang),
                'status' => 'Y',
            ]);

            $ids[$slug] = $info->id;
        }

        return $ids;
    }

    /**
     * @return array<string, int> slug → id
     */
    private function seedTags(): array
    {
        $ids = [];

        foreach (self::TAGS as $slug => $name) {
            $info = ArticleTagInfo::create(['status' => 'Y', 'is_temp' => 'Y', 'created_by' => $this->adminId()]);

            $this->createDetails(ArticleTagDetail::class, $info->id, fn (string $lang) => [
                'name' => $this->t($name, $lang),
                'slug' => $slug,
                'status' => 'Y',
            ]);

            $ids[$slug] = $info->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $article
     * @param  array<string, int>  $categoryIds
     * @param  array<string, int>  $tagIds
     */
    private function seedArticle(array $article, array $categoryIds, array $tagIds): void
    {
        $adminId = $this->adminId();

        $info = ArticleItemInfo::create([
            'article_category_info_id' => $categoryIds[$article['category']],
            'intro_image_id' => SampleFiles::import($article['cover'], self::FOLDER),
            'publish_date' => now()->subDays($article['days_ago'])->setTime(9, 0),
            'view_amount' => $article['views'],
            'status' => 'Y',
            'is_temp' => 'Y',
            'created_by' => $adminId,
        ]);

        $this->createDetails(ArticleItemDetail::class, $info->id, fn (string $lang) => [
            'title' => $this->t($article['title'], $lang),
            'intro_text' => $this->t($article['intro'], $lang),
            'slug' => $article['key'],
            'meta_title' => $this->t($article['title'], $lang),
            'meta_description' => $this->t($article['intro'], $lang),
            'status' => 'Y',
        ]);

        foreach ($article['parts'] as $index => $part) {
            $this->seedPart($info->id, $index, $part);
        }

        $info->tags()->attach(collect($article['tags'])->mapWithKeys(fn (string $slug) => [
            $tagIds[$slug] => ['created_by' => $adminId, 'updated_by' => $adminId],
        ])->all());
    }

    /**
     * @param  array<int|string, mixed>  $part
     */
    private function seedPart(int $itemId, int $index, array $part): void
    {
        $type = $part[0];
        $setting = self::PART_SETTINGS[$type];
        if ($type === 'image' && isset($part['size'])) {
            $setting['size'] = $part['size'];
        }
        if ($type === 'images' && in_array($part['display'], ['grid_lightbox', 'masonry_grid', 'justified_grid'], true)) {
            $setting['columns'] = count($part['files']) >= 4 ? '3' : '2';
        }

        $model = ArticleItemPart::create([
            'article_item_info_id' => $itemId,
            'sort_order' => $index,
            'part_type' => $type,
            'images_display_type' => $type === 'images' ? $part['display'] : null,
            'show_title' => isset($part['title']) ? 'Y' : 'N',
            'status' => 'Y',
            'setting' => $setting,
            'created_by' => $this->adminId(),
        ]);

        if ($type === 'text' || isset($part['title'])) {
            $this->createDetails(ArticleItemPartDetail::class, $model->id, fn (string $lang) => [
                'title' => $this->t($part['title'] ?? null, $lang),
                'detail' => $type === 'text' ? $this->t($part['html'], $lang) : null,
            ]);
        }

        $files = match ($type) {
            'image' => [[$part['file'], $part['alt']]],
            'images' => $part['files'],
            'documents' => array_map(fn (string $path) => [$path, null], $part['files']),
            default => [],
        };

        foreach ($files as $fileIndex => [$path, $alt]) {
            ArticleItemPartFile::create([
                'article_item_part_id' => $model->id,
                'sort_order' => $fileIndex,
                'file_id' => SampleFiles::import($path, $this->folderFor($path)),
                'video_type' => null,
                'description' => $type === 'documents'
                    ? ['pdf_preview' => false, 'show_file_size' => true]
                    : $alt,
                'created_by' => $this->adminId(),
            ]);
        }
    }

    private function folderFor(string $path): string
    {
        return match (true) {
            str_starts_with($path, 'images/guide/') => 'ตัวอย่าง - ภาพหน้าจอ',
            str_starts_with($path, 'files/') => 'ตัวอย่าง - เอกสาร',
            str_starts_with($path, 'images/banner/') => 'ตัวอย่าง - ป้ายโฆษณา',
            default => self::FOLDER,
        };
    }
}
