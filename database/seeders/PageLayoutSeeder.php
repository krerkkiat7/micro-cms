<?php

namespace Database\Seeders;

use App\Models\ArticleCategoryDetail;
use App\Models\ArticleItemDetail;
use App\Models\PageItemColumn;
use App\Models\PageItemColumnDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemRowDetail;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetDetail;
use App\Support\PageWidget\CustomTextWidget;
use App\Support\PageWidget\GridArticleWidget;
use App\Support\PageWidget\PageWidgetRegistry;
use App\Support\PageWidget\SlidesetArticleWidget;
use App\Support\PageWidget\SlideshowBannerWidget;
use Database\Seeders\Support\SampleFiles;
use Database\Seeders\Support\SeedsSampleData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * โครงสร้างหน้า "หน้าแรก" ตัวอย่าง (แถว → คอลัมน์ → widget) — ต้องรันหลัง PageSeeder/FrontMenuSeeder/BannerSeeder
 *
 *   1. Highlight      — Slideshow จากป้ายโฆษณาหมวด Highlight (เต็มความกว้าง)
 *   2. รู้จัก MicroCMS  — Custom Text ข้อความ (7) + รูปภาพ (5) บนรูปพื้นหลังจาง ๆ
 *   3. ข่าวสาร         — Slideset บทความหมวดข่าวสาร + ปุ่มอ่านทั้งหมด
 *   4. การใช้งานระบบ   — Grid แบบการ์ด หมวดการใช้งานระบบ (พื้นสีฟ้าอ่อน)
 *   5. บริการภายนอก   — Grid แบบแถวแสดงวันที่ หมวดการตั้งค่าบริการภายนอก
 *
 * widget บันทึกผ่าน PageWidgetRegistry::find()->save() (ค่าที่ไม่ระบุใช้ค่าเริ่มต้นของ widget). เรียกผ่าน SampleDataSeeder เท่านั้น
 */
class PageLayoutSeeder extends Seeder
{
    use SeedsSampleData;
    use WithoutModelEvents;

    private const HEADING = ['title_font_size' => 32, 'title_font_family' => 'Prompt', 'title_color' => '#0F2A5C', 'title_bold' => 'Y', 'subtitle_font_size' => 18, 'subtitle_color' => '#64748B'];

    private const SECTION_PADDING = ['use_padding' => 'Y', 'padding_top' => 56, 'padding_right' => 16, 'padding_bottom' => 56, 'padding_left' => 16];

    private PageItemInfo $page;

    private int $rowOrder = 0;

    public function run(): void
    {
        $this->page = PageItemInfo::findOrFail(PageSeeder::homeId());

        $this->highlightRow();
        $this->introRow();
        $this->newsRow();
        $this->userGuideRow();
        $this->externalServicesRow();

        $this->page->forceFill(['layout_updated_at' => now(), 'layout_updated_by' => $this->adminId()])->save();
    }

    private function highlightRow(): void
    {
        $row = $this->row(['title' => ['th' => 'Highlight', 'en' => 'Highlight']], ['use_container' => 'N', 'show_title' => 'N']);
        $column = $this->column($row, 12);

        $this->widget($column, SlideshowBannerWidget::TYPE, ['th' => 'ป้ายโฆษณา Highlight', 'en' => 'Highlight banners'], [
            'banner_category_info_id' => BannerSeeder::highlightId(),
            'sort_by' => 'order_asc',
            'transition_effect' => 'fade',
            'aspect_ratio' => '21:9',
            'autoplay_interval' => 6,
            'show_intro_text' => 'Y',
            'text_align' => 'bottom left',
            'text_width' => 'container',
            'title_font_size' => 44,
            'title_font_family' => 'Prompt',
            'intro_text_font_size' => 20,
        ]);
    }

    private function introRow(): void
    {
        $row = $this->row(['title' => ['th' => 'รู้จัก MicroCMS', 'en' => 'About MicroCMS']], [
            'show_title' => 'N',
            'background_color' => '#F8FBFF',
            'background_image_id' => SampleFiles::import('images/page/home-pattern.jpg', 'ตัวอย่าง - หน้าเพจ'),
            'background_size' => 'cover',
            'background_repeat' => 'no-repeat',
            'background_position' => 'center',
            'padding_top' => 72,
            'padding_bottom' => 72,
            'gap_x' => 48,
        ] + self::SECTION_PADDING);

        $text = $this->column($row, 7);
        $this->widget($text, CustomTextWidget::TYPE, ['th' => 'แนะนำ MicroCMS', 'en' => 'Introducing MicroCMS'], ['parts' => [
            $this->customTextPart('text', [
                'th' => ['title' => 'รู้จัก MicroCMS', 'detail' => '<p>ระบบจัดการเนื้อหาเว็บไซต์ขนาดเล็กที่ <strong>ติดตั้งง่าย ใช้งานง่าย</strong> ผู้ดูแลปรับเนื้อหา หน้าตา และเมนูได้เองทั้งหมดจากหลังบ้าน</p>'
                    .'<ul><li>บทความ หน้าเพจ ป้ายโฆษณา Intropage และ Popup</li><li>จัดหน้าเพจเองด้วยแถว คอลัมน์ และ widget</li><li>รองรับภาษาไทย/อังกฤษ พร้อม SEO และ sitemap อัตโนมัติ</li><li>ผู้ใช้งาน สิทธิ์ และประวัติการใช้งานครบถ้วน</li></ul>'
                    .'<p><a href="/th/article/item/'.$this->introducingId().'/introducing-microcms">อ่านแนะนำระบบเพิ่มเติม →</a></p>'],
                'en' => ['title' => 'Meet MicroCMS', 'detail' => '<p>A small website content management system that is <strong>easy to install and easy to use</strong>. Administrators manage content, appearance and menus entirely from the back office.</p>'
                    .'<ul><li>Articles, pages, banners, an intro page and popups</li><li>Lay out pages yourself with rows, columns and widgets</li><li>Thai and English, with automatic SEO and sitemap</li><li>Users, permissions and complete activity history</li></ul>'
                    .'<p><a href="/en/article/item/'.$this->introducingId().'/introducing-microcms">Read more about MicroCMS →</a></p>'],
            ], ['title_font_size' => 34, 'title_font_family' => 'Prompt', 'title_color' => '#0F2A5C']),
        ]]);

        $image = $this->column($row, 5);
        $this->widget($image, CustomTextWidget::TYPE, ['th' => 'ภาพประกอบ', 'en' => 'Illustration'], ['parts' => [
            $this->customTextPart('image', [], [], [
                'setting' => ['alignment' => 'center', 'size' => 'full', 'show_caption' => false],
                'files' => [[
                    'file_id' => SampleFiles::import('images/page/home-feature.jpg', 'ตัวอย่าง - หน้าเพจ'),
                    'description' => ['th' => 'ภาพประกอบโมดูลของ MicroCMS', 'en' => 'An illustration of MicroCMS modules'],
                ]],
            ]),
        ]]);
    }

    private function newsRow(): void
    {
        $row = $this->row([
            'title' => ['th' => 'ข่าวสาร', 'en' => 'News'],
            'subtitle' => ['th' => 'อัปเดตล่าสุดจาก MicroCMS', 'en' => 'The latest from MicroCMS'],
        ], self::SECTION_PADDING + self::HEADING + ['show_title' => 'Y', 'background_color' => '#FFFFFF']);

        $this->widget($this->column($row, 12), SlidesetArticleWidget::TYPE, ['th' => 'ข่าวสารล่าสุด', 'en' => 'Latest news'], [
            'article_category_info_id' => $this->categoryId('news'),
            'per_row_pc' => 3,
            'per_row_notebook' => 3,
            'per_row_tablet' => 2,
            'per_row_mobile' => 1,
            'autoplay' => 'Y',
            'border_color' => '#DBEAFE',
            'title_lines' => 2,
            'title_color' => '#0F2A5C',
            'intro_text_color' => '#475569',
            'show_views' => 'Y',
        ] + $this->readAll('news', ['th' => 'ดูข่าวสารทั้งหมด', 'en' => 'View all news'], ['read_all_style' => 'pill', 'read_all_background' => '#2563EB']));
    }

    private function userGuideRow(): void
    {
        $row = $this->row([
            'title' => ['th' => 'การใช้งานระบบ', 'en' => 'User Guide'],
            'subtitle' => ['th' => 'เรียนรู้การใช้งานแต่ละโมดูลทีละขั้นตอน', 'en' => 'Learn each module step by step'],
        ], self::SECTION_PADDING + self::HEADING + ['show_title' => 'Y', 'background_color' => '#EFF6FF']);

        $this->widget($this->column($row, 12), GridArticleWidget::TYPE, ['th' => 'คู่มือการใช้งาน', 'en' => 'User guides'], [
            'article_category_info_id' => $this->categoryId('user-guide'),
            'display_type' => 'card',
            'max_items' => 6,
            'per_row_pc' => 3,
            'per_row_notebook' => 3,
            'per_row_tablet' => 2,
            'per_row_mobile' => 1,
            'border_color' => '#BFDBFE',
            'show_intro_text' => 'Y',
            'title_lines' => 2,
            'title_color' => '#0F2A5C',
            'intro_text_color' => '#475569',
            'show_date' => 'N',
        ] + $this->readAll('user-guide', ['th' => 'ดูคู่มือทั้งหมด', 'en' => 'View all guides'], ['read_all_background' => '#1D4ED8']));
    }

    private function externalServicesRow(): void
    {
        $row = $this->row([
            'title' => ['th' => 'การตั้งค่าบริการภายนอก', 'en' => 'External Services'],
            'subtitle' => ['th' => 'เชื่อมต่อ CAPTCHA แผนที่ สถิติ และอีเมล', 'en' => 'Connect CAPTCHA, maps, analytics and email'],
        ], self::SECTION_PADDING + self::HEADING + ['show_title' => 'Y', 'background_color' => '#FFFFFF']);

        $this->widget($this->column($row, 12), GridArticleWidget::TYPE, ['th' => 'บริการภายนอก', 'en' => 'External services'], [
            'article_category_info_id' => $this->categoryId('external-services'),
            'display_type' => 'row_date',
            'content_align' => 'center',
            'per_row_pc' => 2,
            'per_row_notebook' => 2,
            'per_row_tablet' => 1,
            'per_row_mobile' => 1,
            'border_color' => '#DBEAFE',
            'show_intro_text' => 'Y',
            'title_color' => '#0F2A5C',
            'intro_text_color' => '#475569',
            'date_box_background' => '#DBEAFE',
            'date_day_color' => '#1E3A8A',
            'date_month_color' => '#3B82F6',
        ] + $this->readAll('external-services', ['th' => 'ดูทั้งหมด', 'en' => 'View all'], [
            'read_all_style' => 'link',
            'read_all_position' => 'bottom_right',
            'read_all_color' => '#1D4ED8',
            'read_all_icon' => 'chevron_right',
        ]));
    }

    /**
     * @param  array<string, array<string, string>>  $text  title / subtitle / intro (แยกภาษา)
     * @param  array<string, mixed>  $attributes
     */
    private function row(array $text, array $attributes): PageItemRow
    {
        $row = PageItemRow::create($attributes + [
            'page_item_info_id' => $this->page->id,
            'sort_order' => $this->rowOrder++,
            'use_container' => 'Y',
            'background_color' => 'transparent',
            'status' => 'Y',
            'created_by' => $this->adminId(),
        ]);

        $this->createDetails(PageItemRowDetail::class, $row->id, fn (string $lang) => [
            'title' => $this->t($text['title'], $lang),
            'subtitle' => $this->t($text['subtitle'] ?? null, $lang),
            'intro_text' => $this->t($text['intro'] ?? null, $lang),
            'status' => 'Y',
        ]);

        return $row;
    }

    private function column(PageItemRow $row, int $size): PageItemColumn
    {
        $column = PageItemColumn::create([
            'page_item_row_id' => $row->id,
            'sort_order' => PageItemColumn::where('page_item_row_id', $row->id)->count(),
            'show_title' => 'N',
            'column_size' => $size,
            'background_color' => 'transparent',
            'status' => 'Y',
            'created_by' => $this->adminId(),
        ]);

        $this->createDetails(PageItemColumnDetail::class, $column->id, fn (string $lang) => [
            'title' => $this->t(['th' => 'คอลัมน์ '.($column->sort_order + 1), 'en' => 'Column '.($column->sort_order + 1)], $lang),
            'status' => 'Y',
        ]);

        return $column;
    }

    /**
     * @param  array<string, string>  $title
     * @param  array<string, mixed>  $setting
     */
    private function widget(PageItemColumn $column, string $type, array $title, array $setting): void
    {
        $widget = PageItemWidget::create([
            'page_item_column_id' => $column->id,
            'sort_order' => 0,
            'show_title' => 'N',
            'widget_type' => $type,
            'background_color' => 'transparent',
            'status' => 'Y',
            'created_by' => $this->adminId(),
        ]);

        $this->createDetails(PageItemWidgetDetail::class, $widget->id, fn (string $lang) => [
            'title' => $this->t($title, $lang),
            'status' => 'Y',
        ]);

        PageWidgetRegistry::find($type)->save($widget->id, $setting, $this->adminId());
    }

    /**
     * ปุ่ม "อ่านทั้งหมด" ลิงก์ไปเมนูหมวดหมู่
     *
     * @param  array<string, string>  $text
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function readAll(string $menuKey, array $text, array $style = []): array
    {
        return $style + [
            'show_read_all' => 'Y',
            'read_all_link_type' => 'menu',
            'read_all_menu_id' => FrontMenuSeeder::id($menuKey),
            'read_all_text' => $text,
        ];
    }

    /**
     * part ของ Custom Text ตามรูปแบบที่ CustomTextWidget::save() รับ
     *
     * @param  array<string, array{title?: string, detail?: string}>  $detail
     * @param  array<string, mixed>  $titleStyle
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function customTextPart(string $type, array $detail, array $titleStyle = [], array $extra = []): array
    {
        return $extra + $titleStyle + [
            'part_type' => $type,
            'images_display_type' => null,
            'show_title' => $detail !== [] ? 'Y' : 'N',
            'status' => 'Y',
            'setting' => [],
            'title_font_size' => CustomTextWidget::DEFAULT_TITLE_FONT_SIZE,
            'title_bold' => 'Y',
            'title_font_family' => 'Sarabun',
            'title_align' => 'left',
            'title_color' => '#000000',
            'detail' => $detail,
            'files' => [],
        ];
    }

    private function categoryId(string $slug): ?int
    {
        return ArticleCategoryDetail::where('slug', $slug)->value('id');
    }

    private function introducingId(): ?int
    {
        return ArticleItemDetail::where('slug', 'introducing-microcms')->value('id');
    }
}
