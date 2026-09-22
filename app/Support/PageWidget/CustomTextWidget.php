<?php

namespace App\Support\PageWidget;

use App\Models\FileInfo;
use App\Models\PageItemWidgetCustomtextPart;
use App\Models\PageItemWidgetCustomtextPartDetail;
use App\Models\PageItemWidgetCustomtextPartFile;
use App\Support\PageTextStyle;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * widget "Custom Text" — กรอกเนื้อหาเอง แบ่งเป็น "part" เรียงลำดับได้หลายรายการต่อ 1 widget (text, image, images, video)
 * เหมือนระบบ part ของบทความ (ArticleItemPart) ต่างจาก widget ประเภทอื่นที่ตั้งค่าเป็นแถวเดียวต่อ widget จึงไม่ extend
 * SettingsWidget (ฐานนั้นออกแบบไว้สำหรับ "1 widget = 1 แถวตั้งค่า" เท่านั้น) implement PageWidgetType ตรง ๆ แทน
 *
 * relation() เป็น hasMany (PageItemWidget::customtextParts) จึงได้ Collection ใน toArray() ไม่ใช่ Model เดี่ยวเหมือนประเภทอื่น
 * save() แทนที่ part ทั้งหมดของ widget ด้วยชุดที่ส่งมาใหม่ทุกครั้ง (เทียบเคียง ArticleItemController::syncParts) เพราะฟอร์ม
 * ส่งเนื้อหาทั้งชุดมาใหม่เสมอ การลบ/เพิ่ม/สลับลำดับ part ที่ผู้ใช้ทำในหน้าจอจึงจัดการง่ายกว่าการ diff เอง
 *
 * หัวเรื่องของแต่ละ part จัดรูปแบบได้เอง (ขนาด/ฟอนต์/จัดตำแหน่ง/สี — คอลัมน์ title_* บน PageItemWidgetCustomtextPart)
 * ไม่มี preview() แบบ ajax เหมือนประเภทอื่น (ที่ดึงตัวอย่างจากหมวดหมู่) เพราะเนื้อหาของ Custom Text คือค่าที่ตั้งเองอยู่แล้ว
 * ฝั่งหน้าจอ (CustomTextPreview.vue) จึงแสดงตัวอย่างจากค่าที่กำลังแก้ไขตรง ๆ ไม่เรียก endpoint ตัวอย่าง
 */
class CustomTextWidget implements PageWidgetType
{
    public const TYPE = 'customtext';

    /** part_type ที่รองรับ (ตัดเอกสารออกจากชุดของบทความ เพราะ widget นี้เน้นข้อความ/สื่อ ไม่ใช่เอกสารแนบ) */
    public const PART_TYPES = ['text', 'image', 'images', 'video'];

    /** images_display_type ที่ใช้ได้ (เฉพาะ part_type = images) — ชุดเดียวกับ ArticleItemPart */
    public const IMAGES_DISPLAY_TYPES = [
        'thumbnail_carousel', 'multi_carousel', 'grid_lightbox',
        'full_width_slider', 'masonry_grid', 'justified_grid', 'stacked_cards',
    ];

    public const DEFAULT_TITLE_FONT_SIZE = 20;

    public const DEFAULT_TITLE_BOLD = 'Y';

    public const DEFAULT_TITLE_ALIGN = 'left';

    public function type(): string
    {
        return self::TYPE;
    }

    public function relation(): string
    {
        return 'customtextParts';
    }

    public function eagerRelations(): array
    {
        return [
            'customtextParts',
            'customtextParts.files',
            'customtextParts.files.file',
            'customtextParts.files.coverImage',
            'customtextParts.details',
        ];
    }

    public function rules(): array
    {
        $rules = [
            'parts' => ['nullable', 'array'],
            'parts.*.part_type' => ['required', Rule::in(self::PART_TYPES)],
            'parts.*.images_display_type' => ['nullable', Rule::in(self::IMAGES_DISPLAY_TYPES)],
            'parts.*.show_title' => ['nullable', Rule::in(['Y', 'N'])],
            'parts.*.status' => ['nullable', Rule::in(['Y', 'N'])],
            'parts.*.setting' => ['nullable', 'array'],
            'parts.*.title_font_size' => ['nullable', 'integer', 'between:'.PageTextStyle::FONT_SIZE_MIN.','.PageTextStyle::FONT_SIZE_MAX],
            'parts.*.title_bold' => ['nullable', Rule::in(['Y', 'N'])],
            'parts.*.title_font_family' => ['nullable', Rule::in(PageTextStyle::fontNames())],
            'parts.*.title_align' => ['nullable', Rule::in(PageTextStyle::ALIGNS)],
            'parts.*.title_color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'parts.*.detail' => ['nullable', 'array'],
            'parts.*.files' => ['nullable', 'array'],
            'parts.*.files.*.file_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query->where('status', 'Y')->whereNull('deleted_at')),
            ],
            'parts.*.files.*.cover_image_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query->where('status', 'Y')->whereNull('deleted_at')),
            ],
            'parts.*.files.*.video_type' => ['nullable', Rule::in(['file', 'youtube'])],
            'parts.*.files.*.youtube_url' => [
                'nullable', 'string', 'max:500',
                'regex:#^https?://(www\.)?(youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/)[\w-]+#i',
            ],
            'parts.*.files.*.description' => ['nullable', 'array'],
        ];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["parts.*.detail.{$lang}.title"] = ['nullable', 'string', 'max:250'];
            $rules["parts.*.detail.{$lang}.detail"] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'parts.*.part_type.required' => 'กรุณาเลือกประเภทของแต่ละส่วนเนื้อหา',
            'parts.*.part_type.in' => 'ประเภทของส่วนเนื้อหาไม่ถูกต้อง',
            'parts.*.title_font_size.between' => 'ขนาดฟอนต์หัวเรื่องต้องอยู่ระหว่าง '.PageTextStyle::FONT_SIZE_MIN.' - '.PageTextStyle::FONT_SIZE_MAX,
            'parts.*.title_font_family.in' => 'ฟอนต์หัวเรื่องไม่ถูกต้อง',
            'parts.*.title_align.in' => 'การจัดตำแหน่งหัวเรื่องไม่ถูกต้อง',
            'parts.*.title_color.regex' => 'รูปแบบสีตัวอักษรหัวเรื่องไม่ถูกต้อง',
            'parts.*.files.*.youtube_url.regex' => 'ลิงก์ YouTube ไม่ถูกต้อง',
        ];
    }

    /**
     * เนื้อหาของ Custom Text คือค่าที่ตั้งเอง ไม่ใช่ตัวอย่างจากหมวดหมู่ — หน้าจอแสดงตัวอย่างจากค่าที่กำลังแก้ไขตรง ๆ
     * ไม่เรียก endpoint ตัวอย่าง (ดูคอมเมนต์บนคลาส) จึงไม่มีกฎเฉพาะสำหรับ endpoint นั้น
     */
    public function previewRules(): array
    {
        return [];
    }

    public function defaults(): array
    {
        return ['parts' => []];
    }

    /**
     * แทนที่ part ทั้งหมดของ widget ด้วยชุดที่ส่งมาใหม่ (เทียบเคียง ArticleItemController::syncParts)
     */
    public function save(int $widgetId, array $setting, ?int $actorId): void
    {
        $existingParts = PageItemWidgetCustomtextPart::where('page_item_widget_id', $widgetId)->with(['files', 'details'])->get();

        foreach ($existingParts as $part) {
            foreach ($part->files as $file) {
                $file->deleted_by = $actorId;
                $file->save();
                $file->delete();
            }

            PageItemWidgetCustomtextPartDetail::where('id', $part->id)->update(['deleted_by' => $actorId]);
            PageItemWidgetCustomtextPartDetail::where('id', $part->id)->delete();

            $part->deleted_by = $actorId;
            $part->save();
            $part->delete();
        }

        foreach (array_values($setting['parts'] ?? []) as $index => $partData) {
            $part = PageItemWidgetCustomtextPart::create([
                'page_item_widget_id' => $widgetId,
                'sort_order' => $index,
                'part_type' => $partData['part_type'] ?? 'text',
                'images_display_type' => $partData['images_display_type'] ?? null,
                'show_title' => $partData['show_title'] ?? 'Y',
                'status' => $partData['status'] ?? 'Y',
                'setting' => $partData['setting'] ?? null,
                'title_font_size' => $partData['title_font_size'] ?? self::DEFAULT_TITLE_FONT_SIZE,
                'title_bold' => $partData['title_bold'] ?? self::DEFAULT_TITLE_BOLD,
                'title_font_family' => $partData['title_font_family'] ?? PageTextStyle::DEFAULT_FONT,
                'title_align' => $partData['title_align'] ?? self::DEFAULT_TITLE_ALIGN,
                'title_color' => $partData['title_color'] ?? PageTextStyle::DEFAULT_COLOR,
                'created_by' => $actorId,
            ]);

            foreach ($partData['detail'] ?? [] as $lang => $detail) {
                $title = trim((string) ($detail['title'] ?? ''));
                $text = trim((string) ($detail['detail'] ?? ''));

                if ($title === '' && $text === '') {
                    continue;
                }

                PageItemWidgetCustomtextPartDetail::create([
                    'id' => $part->id,
                    'lang' => $lang,
                    'title' => $title !== '' ? $title : null,
                    'detail' => $text !== '' ? $text : null,
                    'created_by' => $actorId,
                ]);
            }

            foreach ($partData['files'] ?? [] as $fileIndex => $fileData) {
                if (empty($fileData['file_id']) && empty($fileData['youtube_url'])) {
                    continue;
                }

                PageItemWidgetCustomtextPartFile::create([
                    'page_item_widget_customtext_part_id' => $part->id,
                    'sort_order' => $fileIndex,
                    'file_id' => $fileData['file_id'] ?? null,
                    'cover_image_id' => $fileData['cover_image_id'] ?? null,
                    'video_type' => $fileData['video_type'] ?? null,
                    'youtube_url' => $fileData['youtube_url'] ?? null,
                    'description' => $fileData['description'] ?? null,
                    'created_by' => $actorId,
                ]);
            }
        }
    }

    public function toArray(mixed $row): array
    {
        if (! $row instanceof Collection) {
            return $this->defaults();
        }

        $languages = Setting::selectedLanguages();

        return [
            'parts' => $row->map(function (PageItemWidgetCustomtextPart $part) use ($languages) {
                $detailsByLang = $part->details->keyBy('lang');

                return [
                    'id' => $part->id,
                    'part_type' => $part->part_type,
                    'images_display_type' => $part->images_display_type,
                    'show_title' => $part->show_title,
                    'status' => $part->status,
                    'setting' => $part->setting ?? [],
                    'title_font_size' => (int) $part->title_font_size,
                    'title_bold' => $part->title_bold,
                    'title_font_family' => $part->title_font_family,
                    'title_align' => $part->title_align,
                    'title_color' => $part->title_color,
                    'detail' => collect($languages)->mapWithKeys(fn (string $lang) => [
                        $lang => [
                            'title' => $detailsByLang->get($lang)?->title ?? '',
                            'detail' => $detailsByLang->get($lang)?->detail ?? '',
                        ],
                    ]),
                    'files' => $part->files->map(fn (PageItemWidgetCustomtextPartFile $file) => [
                        'file' => $file->file ? $this->fileToArray($file->file) : null,
                        'cover_image' => $file->coverImage ? $this->fileToArray($file->coverImage) : null,
                        'video_type' => $file->video_type,
                        'youtube_url' => $file->youtube_url,
                        'description' => $file->description ?? [],
                    ])->values(),
                ];
            })->values(),
        ];
    }

    public function softDelete(array $widgetIds, ?int $actorId): void
    {
        if ($widgetIds === []) {
            return;
        }

        $partIds = PageItemWidgetCustomtextPart::whereIn('page_item_widget_id', $widgetIds)->pluck('id');

        if ($partIds->isEmpty()) {
            return;
        }

        PageItemWidgetCustomtextPartFile::whereIn('page_item_widget_customtext_part_id', $partIds)
            ->update(['deleted_by' => $actorId, 'deleted_at' => now()]);
        PageItemWidgetCustomtextPartDetail::whereIn('id', $partIds)->update(['deleted_by' => $actorId, 'deleted_at' => now()]);
        PageItemWidgetCustomtextPart::whereIn('id', $partIds)->update(['deleted_by' => $actorId, 'deleted_at' => now()]);
    }

    public function options(): array
    {
        return [];
    }

    /** ไม่ใช้ (ดูคอมเมนต์บนคลาส) */
    public function preview(array $setting): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileToArray(FileInfo $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'hash_name' => $file->hash_name,
            'extension' => $file->extension,
            'file_size' => $file->file_size,
            'is_image' => $file->isImage(),
            'created_at' => $file->created_at,
        ];
    }
}
