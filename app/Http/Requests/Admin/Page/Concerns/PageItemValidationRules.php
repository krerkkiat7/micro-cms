<?php

namespace App\Http\Requests\Admin\Page\Concerns;

use App\Models\PageItemWidget;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * กฎ validation ของโมดูล Page — ใช้ร่วมกันระหว่าง Store/UpdatePageItemRequest (ข้อมูลทั่วไป) และ
 * UpdatePageItemLayoutRequest (โครงสร้าง แถว → คอลัมน์ → widget) เทียบเคียง ArticleItemValidationRules
 */
trait PageItemValidationRules
{
    /** สีพื้นหลัง: รหัส hex หรือคำว่า transparent (ตัวเลือกใน ColorPickerInput) */
    private const COLOR_REGEX = '/^(transparent|#[0-9a-fA-F]{3,8})$/';

    /**
     * กฎของ "ไฟล์" ที่ต้องมีอยู่จริงใน file_info (ไม่ผูกกับเจ้าของไฟล์ — เลือกไฟล์ของคนอื่นมาใช้ได้ตามที่ตั้งใจ)
     *
     * @return array<int, mixed>
     */
    private function fileRule(): array
    {
        return [
            'nullable', 'integer',
            Rule::exists('file_info', 'id')->where(fn ($query) => $query
                ->where('status', 'Y')
                ->whereNull('deleted_at')),
        ];
    }

    /**
     * กฎของกลุ่มฟิลด์พื้นหลัง (สี/รูป/repeat/size/attachment/position) — $prefix ใช้ซ้อนใน rows.*. / columns.*.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function backgroundRules(string $prefix = ''): array
    {
        return [
            "{$prefix}background_color" => ['nullable', 'string', 'max:20', 'regex:'.self::COLOR_REGEX],
            "{$prefix}background_image_id" => $this->fileRule(),
            "{$prefix}background_repeat" => ['nullable', Rule::in(['repeat', 'no-repeat', 'repeat-x', 'repeat-y'])],
            "{$prefix}background_size" => ['nullable', Rule::in(['auto', 'cover', 'contain'])],
            "{$prefix}background_attachment" => ['nullable', Rule::in(['scroll', 'fixed'])],
            "{$prefix}background_position" => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * กฎของฟิลด์ข้อมูลร่วมของหน้า (ไม่แยกภาษา)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function commonRules(): array
    {
        return [
            'intro_image_id' => $this->fileRule(),
            'status' => ['required', Rule::in(['Y', 'N'])],
        ] + $this->backgroundRules();
    }

    /**
     * กฎของฟิลด์แยกภาษาของหน้า (detail.<lang>.*) — required เฉพาะ "ชื่อ" ของภาษาหลักเท่านั้น (site.lang_default)
     * slug unique ต่อภาษา โดยไม่นับหน้าที่ถูกลบไปแล้ว (page_item_detail ไม่เคยถูก soft delete พร้อมพาเรนต์ จึงเช็กผ่าน
     * page_item_info.deleted_at) $ignoreId = id ของหน้าปัจจุบัน (ตอนแก้ไข) เพื่อไม่ให้ slug ชนกับแถวของตัวเอง
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
                Rule::unique('page_item_detail', 'slug')
                    ->where(fn ($query) => $query
                        ->where('lang', $lang)
                        ->whereIn('id', fn ($sub) => $sub->select('id')->from('page_item_info')->whereNull('deleted_at')))
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
     * กฎของโครงสร้างทั้งหน้า (rows.*.columns.*.widgets.*) — ฟิลด์แยกภาษาของทุกชั้นมีแค่ title/intro_text
     * และเป็น nullable ทั้งหมด (ต่างจากตัวหน้าที่ภาษาหลักต้องมีชื่อ) ส่วน id ที่ส่งมาต้องเป็นของหน้านี้จริง
     * ตรวจใน UpdatePageItemLayoutRequest::withValidator()
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function layoutRules(): array
    {
        $rules = [
            'rows' => ['nullable', 'array'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.status' => ['required', Rule::in(['Y', 'N'])],
            'rows.*.show_title' => ['required', Rule::in(['Y', 'N'])],
            'rows.*.use_container' => ['required', Rule::in(['Y', 'N'])],
            'rows.*.detail' => ['nullable', 'array'],
            'rows.*.columns' => ['nullable', 'array'],

            'rows.*.columns.*.id' => ['nullable', 'integer'],
            'rows.*.columns.*.status' => ['required', Rule::in(['Y', 'N'])],
            'rows.*.columns.*.show_title' => ['required', Rule::in(['Y', 'N'])],
            'rows.*.columns.*.column_size' => ['required', 'integer', 'between:1,12'],
            'rows.*.columns.*.detail' => ['nullable', 'array'],
            'rows.*.columns.*.widgets' => ['nullable', 'array'],

            'rows.*.columns.*.widgets.*.id' => ['nullable', 'integer'],
            'rows.*.columns.*.widgets.*.status' => ['required', Rule::in(['Y', 'N'])],
            'rows.*.columns.*.widgets.*.show_title' => ['required', Rule::in(['Y', 'N'])],
            'rows.*.columns.*.widgets.*.widget_type' => ['required', Rule::in(PageItemWidget::TYPES)],
            'rows.*.columns.*.widgets.*.setting' => ['nullable', 'array'],
            'rows.*.columns.*.widgets.*.detail' => ['nullable', 'array'],
        ]
            + $this->backgroundRules('rows.*.')
            + $this->backgroundRules('rows.*.columns.*.');

        foreach (Setting::selectedLanguages() as $lang) {
            foreach (['rows.*.', 'rows.*.columns.*.', 'rows.*.columns.*.widgets.*.'] as $prefix) {
                $rules["{$prefix}detail.{$lang}.title"] = ['nullable', 'string', 'max:250'];
                $rules["{$prefix}detail.{$lang}.intro_text"] = ['nullable', 'string', 'max:2000'];
            }
        }

        return $rules;
    }
}
