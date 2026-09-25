<?php

namespace App\Support\Front;

use App\Models\PageItemColumn;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemWidget;
use App\Support\PageSpacing;
use App\Support\PageTextStyle;
use App\Support\PageWidget\CategoryListWidget;
use App\Support\PageWidget\CustomTextWidget;
use App\Support\PageWidget\PageWidgetRegistry;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Model;

/**
 * อ่านโครงสร้างหน้าเพจ แถว → คอลัมน์ → widget สำหรับแสดงที่หน้าบ้าน (ต่างจากหน้าโครงสร้างหลังบ้านที่ส่งทุกภาษา/ทุกสถานะให้แก้ไข):
 * - เฉพาะที่แสดงอยู่ (status = Y) ทุกชั้น; widget ประเภทที่ไม่รู้จัก (เช่น placeholder เดิม) ถูกข้าม
 * - ข้อความของภาษาที่ขอ (ยังไม่ได้แปล = ภาษาหลัก), สไตล์ตัวอักษรจัดเป็นก้อน {font_size, font_family, align, color}
 * - พื้นหลังส่ง URL รูปแล้ว (FrontFile) ไม่ส่ง hash
 * - widget ที่ดึงรายการจากหมวดหมู่ได้ `items` จริง (มีลิงก์) / Custom Text ได้ `parts` ที่พร้อมแสดง
 * - `fonts` = ฟอนต์ที่หน้านี้ใช้จริง (ให้โหลดเฉพาะที่ใช้)
 */
final class PageLayoutReader
{
    /** @var array<string, true> */
    private array $fonts = [];

    private function __construct(private readonly string $lang) {}

    /**
     * @return array{background: array<string, mixed>, rows: list<array<string, mixed>>, fonts: list<string>}
     */
    public static function read(PageItemInfo $page, string $lang): array
    {
        $reader = new self($lang);

        $rows = $page->rows()
            ->where('status', 'Y')
            ->with([
                'backgroundImage', 'details',
                'columns' => fn ($query) => $query->where('status', 'Y'),
                'columns.backgroundImage', 'columns.details',
                'columns.widgets' => fn ($query) => $query->where('status', 'Y'),
                'columns.widgets.backgroundImage', 'columns.widgets.details',
                ...PageWidgetRegistry::relations('columns.widgets.'),
            ])
            ->get();

        return [
            'background' => $reader->background($page),
            'rows' => $rows->map(fn (PageItemRow $row) => $reader->row($row))->values()->all(),
            'fonts' => array_keys($reader->fonts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(PageItemRow $row): array
    {
        return [
            'id' => (int) $row->id,
            'use_container' => $row->use_container === 'Y',
            'gap_x' => (int) $row->gap_x,
            'gap_y' => (int) $row->gap_y,
            ...$this->common($row),
            'columns' => $row->columns
                ->map(fn (PageItemColumn $column) => [
                    'id' => (int) $column->id,
                    'column_size' => max(1, min(12, (int) $column->column_size)),
                    ...$this->common($column),
                    'widgets' => $column->widgets
                        ->map(fn (PageItemWidget $widget) => $this->widget($widget))
                        ->filter()
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function widget(PageItemWidget $widget): ?array
    {
        $type = PageWidgetRegistry::find($widget->widget_type);

        if ($type === null) {
            return null;
        }

        $relation = $widget->relationLoaded($type->relation()) ? $widget->getRelation($type->relation()) : null;
        $setting = [];
        $items = [];
        $parts = [];

        if ($type instanceof CustomTextWidget) {
            $parts = FrontParts::map($relation ?? [], $this->lang);

            foreach ($parts as $part) {
                if ($part['title_style']['font_family'] ?? null) {
                    $this->fonts[$part['title_style']['font_family']] = true;
                }
            }
        } else {
            $setting = $this->localizeSetting($type->toArray($relation));

            if ($type instanceof CategoryListWidget) {
                $items = $type->frontItems($setting, $this->lang);
            }

            foreach ($setting as $key => $value) {
                if (str_ends_with($key, 'font_family') && is_string($value)) {
                    $this->fonts[$value] = true;
                }
            }
        }

        return [
            'id' => (int) $widget->id,
            'widget_type' => $widget->widget_type,
            ...$this->common($widget),
            'setting' => (object) $setting,
            'items' => $items,
            'parts' => $parts,
        ];
    }

    /**
     * ค่าตั้งค่าที่แยกภาษา ({lang: ข้อความ} — เช่น ข้อความของปุ่มอ่านทั้งหมด) เหลือเฉพาะภาษาที่แสดง, ลิงก์ที่กรอกเองผ่านตัวกรองลิงก์ปลอดภัย
     *
     * @param  array<string, mixed>  $setting
     * @return array<string, mixed>
     */
    private function localizeSetting(array $setting): array
    {
        $languages = Setting::selectedLanguages();
        $default = Setting::defaultLanguage();

        foreach ($setting as $key => $value) {
            if (is_array($value) && $value !== [] && array_diff(array_keys($value), $languages) === []) {
                $text = trim((string) ($value[$this->lang] ?? ''));
                $setting[$key] = $text !== '' ? $text : trim((string) ($value[$default] ?? ''));
            }
        }

        if (array_key_exists('read_all_url', $setting)) {
            $setting['read_all_url'] = FrontUrl::safeExternal($setting['read_all_url']);
        }

        return $setting;
    }

    /**
     * ส่วนที่แถว/คอลัมน์/widget มีเหมือนกัน: การแสดงหัวเรื่อง, ข้อความ 3 ส่วน + สไตล์, พื้นหลัง, ระยะขอบด้านใน (ปิดใช้งาน = null)
     *
     * @return array<string, mixed>
     */
    private function common(PageItemRow|PageItemColumn|PageItemWidget $model): array
    {
        $detail = FrontLang::pick($model->details, $this->lang);
        $showTitle = $model->show_title === 'Y';
        $styles = [];

        foreach (PageTextStyle::PARTS as $part) {
            $family = (string) ($model->{"{$part}_font_family"} ?: PageTextStyle::DEFAULT_FONT);

            if ($showTitle) {
                $this->fonts[$family] = true;
            }

            $styles["{$part}_style"] = [
                'font_size' => (int) $model->{"{$part}_font_size"},
                'font_family' => $family,
                'align' => (string) ($model->{"{$part}_align"} ?: PageTextStyle::DEFAULT_ALIGN),
                'color' => (string) ($model->{"{$part}_color"} ?: PageTextStyle::DEFAULT_COLOR),
            ];
        }

        return [
            'title' => $showTitle ? trim((string) ($detail?->title ?? '')) : '',
            'subtitle' => $showTitle ? trim((string) ($detail?->subtitle ?? '')) : '',
            'intro_text' => $showTitle ? trim((string) ($detail?->intro_text ?? '')) : '',
            ...$styles,
            'background' => $this->background($model),
            'padding' => PageSpacing::padding($model),
        ];
    }

    /**
     * @return array{color: string|null, image_url: string|null, repeat: string|null, size: string|null, attachment: string|null, position: string|null}
     */
    private function background(Model $model): array
    {
        $image = $model->backgroundImage;
        $imageUrl = $image && $image->status === 'Y' ? FrontFile::url($image->hash_name) : null;

        return [
            'color' => $model->background_color ?: null,
            'image_url' => $imageUrl,
            'repeat' => $imageUrl ? $model->background_repeat : null,
            'size' => $imageUrl ? $model->background_size : null,
            'attachment' => $imageUrl ? $model->background_attachment : null,
            'position' => $imageUrl ? $model->background_position : null,
        ];
    }
}
