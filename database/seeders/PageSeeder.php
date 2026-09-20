<?php

namespace Database\Seeders;

use App\Models\PageItemColumn;
use App\Models\PageItemColumnDetail;
use App\Models\PageItemDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemRowDetail;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetDetail;
use App\Support\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างโมดูล Page — ทำเครื่องหมาย is_temp = 'Y' เพื่อให้ลบออกได้ภายหลัง (หรือจะใช้ต่อไปก็ได้)
 * สร้างหน้าเพจตัวอย่าง 1 หน้า พร้อมโครงสร้าง 3 แถว: แถว hero (1 คอลัมน์เต็ม 12), แถวเนื้อหา (2 คอลัมน์ 8+4 ใน container),
 * แถวมีสีพื้นหลัง (3 คอลัมน์ 4+4+4) — สองแถวแรกเปิดแสดงหัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ, ทุกคอลัมน์มี widget ประเภท placeholder (ประเภท widget จริงรอกำหนด)
 * ไม่ผูกไฟล์ใน file_info (เหมือน IntropageSeeder/BannerSeeder) รันซ้ำได้: หน้าเดิม update, โครงสร้างสร้างเฉพาะเมื่อยังไม่มีแถว
 *
 * รันเดี่ยว: php artisan db:seed --class=PageSeeder
 */
class PageSeeder extends Seeder
{
    use WithoutModelEvents;

    /** @var list<string> */
    private array $languages = [];

    public function run(): void
    {
        $this->languages = Setting::selectedLanguages();
        if ($this->languages === []) {
            $this->languages = ['th', 'en'];
        }

        $defaultLang = Setting::defaultLanguage();

        $title = ['th' => 'หน้าเพจตัวอย่าง', 'en' => 'Sample Page'];
        $intro = [
            'th' => 'หน้าเพจตัวอย่างที่ประกอบจากหลายส่วน (แถว → คอลัมน์ → widget)',
            'en' => 'A sample page built from multiple sections (row → column → widget)',
        ];

        $defaultTitle = $title[$defaultLang] ?? $title['th'];

        $info = PageItemInfo::updateOrCreate(
            ['id' => PageItemDetail::where('lang', $defaultLang)->where('title', $defaultTitle)->value('id')],
            [
                'background_color' => '#ffffff',
                'status' => 'Y',
                'is_temp' => 'Y',
            ],
        );

        foreach ($this->languages as $lang) {
            $values = [
                'title' => $title[$lang] ?? $title['th'],
                'intro_text' => $intro[$lang] ?? $intro['th'],
                'slug' => 'sample-page',
                'meta_title' => $title[$lang] ?? $title['th'],
                'meta_description' => $intro[$lang] ?? $intro['th'],
                'status' => 'Y',
            ];

            // composite key (id+lang) — ห้าม updateOrCreate()/save() ผ่าน model เพราะจะ update ด้วย id อย่างเดียวทุกภาษา
            $query = PageItemDetail::where('id', $info->id)->where('lang', $lang);
            if ($query->exists()) {
                $query->update($values);
            } else {
                PageItemDetail::create(['id' => $info->id, 'lang' => $lang] + $values);
            }
        }

        if ($info->rows()->count() > 0) {
            return;
        }

        $this->seedLayout($info);
    }

    private function seedLayout(PageItemInfo $info): void
    {
        $rows = [
            [
                'title' => ['th' => 'ส่วนบนสุด (Hero)', 'en' => 'Hero'],
                'subtitle' => ['th' => 'ยินดีต้อนรับ', 'en' => 'Welcome'],
                'intro' => ['th' => 'ข้อความเกริ่นนำของส่วนบนสุด', 'en' => 'Introduction of the hero section'],
                'row' => ['show_title' => 'Y', 'use_container' => 'N', 'background_color' => '#eef2ff'],
                'columns' => [
                    [12, ['th' => 'พื้นที่ประชาสัมพันธ์หลัก', 'en' => 'Main highlight'], ['th' => 'Widget ตัวอย่าง', 'en' => 'Sample widget']],
                ],
            ],
            [
                'title' => ['th' => 'ส่วนเนื้อหา', 'en' => 'Content'],
                'subtitle' => ['th' => 'เรื่องที่น่าสนใจ', 'en' => 'Featured'],
                'row' => ['show_title' => 'Y', 'use_container' => 'Y', 'background_color' => 'transparent'],
                'columns' => [
                    [8, ['th' => 'เนื้อหาหลัก', 'en' => 'Main content'], ['th' => 'Widget เนื้อหา', 'en' => 'Content widget']],
                    [4, ['th' => 'แถบด้านข้าง', 'en' => 'Sidebar'], ['th' => 'Widget แถบข้าง', 'en' => 'Sidebar widget']],
                ],
            ],
            [
                'title' => ['th' => 'ส่วนท้ายมีพื้นหลัง', 'en' => 'Highlighted footer'],
                'row' => ['show_title' => 'N', 'use_container' => 'Y', 'background_color' => '#f2f4f7'],
                'columns' => [
                    [4, ['th' => 'คอลัมน์ 1', 'en' => 'Column 1'], ['th' => 'Widget 1', 'en' => 'Widget 1']],
                    [4, ['th' => 'คอลัมน์ 2', 'en' => 'Column 2'], ['th' => 'Widget 2', 'en' => 'Widget 2']],
                    [4, ['th' => 'คอลัมน์ 3', 'en' => 'Column 3'], ['th' => 'Widget 3', 'en' => 'Widget 3']],
                ],
            ],
        ];

        foreach ($rows as $rowIndex => $rowData) {
            $row = PageItemRow::create([
                'page_item_info_id' => $info->id,
                'sort_order' => $rowIndex,
                'show_title' => $rowData['row']['show_title'],
                'use_container' => $rowData['row']['use_container'],
                'background_color' => $rowData['row']['background_color'],
                'status' => 'Y',
            ]);
            $this->details(PageItemRowDetail::class, $row->id, $rowData['title'], $rowData['subtitle'] ?? [], $rowData['intro'] ?? []);

            foreach ($rowData['columns'] as $columnIndex => [$size, $columnTitle, $widgetTitle]) {
                $column = PageItemColumn::create([
                    'page_item_row_id' => $row->id,
                    'sort_order' => $columnIndex,
                    'show_title' => 'N',
                    'column_size' => $size,
                    'background_color' => 'transparent',
                    'status' => 'Y',
                ]);
                $this->details(PageItemColumnDetail::class, $column->id, $columnTitle);

                $widget = PageItemWidget::create([
                    'page_item_column_id' => $column->id,
                    'sort_order' => 0,
                    'show_title' => 'Y',
                    'widget_type' => 'placeholder',
                    'setting' => [],
                    'background_color' => 'transparent',
                    'status' => 'Y',
                ]);
                $this->details(PageItemWidgetDetail::class, $widget->id, $widgetTitle);
            }
        }
    }

    /**
     * สร้างข้อมูลแยกภาษาของแถว/คอลัมน์/widget — ภาษาที่ไม่มีคำแปลในตัวอย่างใช้ภาษาไทยแทน
     *
     * @param  class-string<Model>  $model
     * @param  array<string, string>  $titles
     * @param  array<string, string>  $subtitles
     * @param  array<string, string>  $intros
     */
    private function details(string $model, int $id, array $titles, array $subtitles = [], array $intros = []): void
    {
        foreach ($this->languages as $lang) {
            $model::create([
                'id' => $id,
                'lang' => $lang,
                'title' => $titles[$lang] ?? $titles['th'],
                'subtitle' => $subtitles !== [] ? ($subtitles[$lang] ?? $subtitles['th']) : null,
                'intro_text' => $intros !== [] ? ($intros[$lang] ?? $intros['th']) : null,
                'status' => 'Y',
            ]);
        }
    }
}
