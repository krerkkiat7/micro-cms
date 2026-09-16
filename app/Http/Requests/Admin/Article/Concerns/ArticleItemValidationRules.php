<?php

namespace App\Http\Requests\Admin\Article\Concerns;

use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * กฎ validation ที่ใช้ร่วมกันระหว่าง Store/UpdateArticleItemRequest — แยกเป็น trait เพราะกฎของฟิลด์
 * แยกภาษาและของ part ค่อนข้างยาว ต่างจาก StoreArticleCategoryRequest ที่ยอมให้ซ้ำกันได้เพราะสั้นกว่ามาก
 */
trait ArticleItemValidationRules
{
    /**
     * กฎของฟิลด์ข้อมูลร่วม (ไม่แยกภาษา ไม่รวม part)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function commonRules(): array
    {
        return [
            'article_category_info_id' => [
                'required', 'integer',
                Rule::exists('article_category_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'intro_image_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'publish_date' => ['required', 'date'],
            'publish_down' => ['nullable', 'date', 'after:publish_date'],
            'status' => ['required', Rule::in(['Y', 'N'])],
            'tags' => ['nullable', 'array'],
            'tags.*' => [
                'integer',
                Rule::exists('article_tag_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
        ];
    }

    /**
     * กฎของฟิลด์แยกภาษา (detail.<lang>.*) — required เฉพาะ "ชื่อ" ของภาษาหลักเท่านั้น (site.lang_default)
     * $ignoreId = id ของบทความปัจจุบัน (ตอนแก้ไข) เพื่อไม่ให้ slug ชนกับแถวของตัวเอง
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(?int $ignoreId = null): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.title"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:500'];
            $rules["detail.{$lang}.intro_text"] = ['nullable', 'string', 'max:2000'];
            $rules["detail.{$lang}.slug"] = [
                'nullable', 'string', 'max:250',
                Rule::unique('article_item_detail', 'slug')
                    ->where(fn ($query) => $query->where('lang', $lang))
                    ->ignore($ignoreId, 'id'),
            ];
            $rules["detail.{$lang}.meta_title"] = ['nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.meta_description"] = ['nullable', 'string', 'max:500'];
            $rules["detail.{$lang}.meta_keywords"] = ['nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.og_title"] = ['nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.og_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * กฎของเนื้อหาแบบแบ่ง part (parts.*) — ทุกฟิลด์เป็น nullable (อนุญาตให้บันทึกแบบไม่มี part
     * หรือ part ที่ยังกรอกไม่ครบได้) ยกเว้น part_type ที่ต้องมีเสมอเพื่อให้รู้ว่าจะ render/บันทึกแบบไหน
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function partRules(): array
    {
        $rules = [
            'parts' => ['nullable', 'array'],
            'parts.*.part_type' => ['required', Rule::in(['text', 'image', 'images', 'video', 'document', 'documents'])],
            'parts.*.images_display_type' => [
                'nullable',
                Rule::in(['thumbnail_carousel', 'multi_carousel', 'grid_lightbox', 'full_width_slider', 'masonry_grid', 'justified_grid', 'stacked_cards']),
            ],
            'parts.*.setting' => ['nullable', 'array'],
            'parts.*.detail' => ['nullable', 'array'],
            'parts.*.files' => ['nullable', 'array'],
            'parts.*.files.*.file_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'parts.*.files.*.cover_image_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
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
}
