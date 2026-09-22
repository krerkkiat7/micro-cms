<?php

namespace App\Http\Requests\Admin\System\FrontMenu;

use App\Support\FrontMenuType;
use App\Support\PageTextStyle;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFrontMenuRequest extends FormRequest
{
    /** ค่าที่เลือกได้ของ header_content_align — ตรงกับ BACKGROUND_POSITION_STYLES ฝั่ง Vue (utils/intropageBackground.ts) */
    public const ALIGN_VALUES = [
        'top left', 'top', 'top right',
        'left', 'center', 'right',
        'bottom left', 'bottom', 'bottom right',
        '',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'parent_id' => [
                'nullable', 'integer',
                // Parent เลือกได้เฉพาะเมนูประเภท "เมนูหัวข้อ" ที่ยังไม่ถูกลบ
                Rule::exists('front_menu_info', 'id')->where(fn ($query) => $query
                    ->where('menu_type', FrontMenuType::HEADING)
                    ->whereNull('deleted_at')),
            ],
            'menu_type' => ['required', Rule::in(FrontMenuType::values())],
            'target_article_category_id' => [
                Rule::requiredIf(fn () => $this->input('menu_type') === FrontMenuType::ARTICLE_CATEGORY),
                'nullable', 'integer',
                Rule::exists('article_category_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')->whereNull('deleted_at')),
            ],
            'target_article_item_id' => [
                Rule::requiredIf(fn () => $this->input('menu_type') === FrontMenuType::ARTICLE_ITEM),
                'nullable', 'integer',
                Rule::exists('article_item_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')->whereNull('deleted_at')),
            ],
            'target_page_item_id' => [
                Rule::requiredIf(fn () => $this->input('menu_type') === FrontMenuType::PAGE),
                'nullable', 'integer',
                Rule::exists('page_item_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')->whereNull('deleted_at')),
            ],
            'url' => [
                Rule::requiredIf(fn () => $this->input('menu_type') === FrontMenuType::EXTERNAL),
                'nullable', 'string', 'max:500',
            ],
            'link_target' => ['required', Rule::in(['_self', '_blank'])],
            'is_home' => ['required', Rule::in(['Y', 'N'])],

            'show_header_image' => ['required', Rule::in(['Y', 'N'])],
            'header_image_id' => [
                'nullable', 'integer',
                // เช็กแค่ว่าไฟล์นี้มีอยู่จริงและยังใช้งานอยู่ — ไม่จำกัดว่าต้องเป็นไฟล์ของใคร (เทียบเคียง sys_user.profile_image_id)
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')->whereNull('deleted_at')),
            ],

            'show_title' => ['required', Rule::in(['Y', 'N'])],
            'title_font_size' => ['required', 'integer', 'min:'.PageTextStyle::FONT_SIZE_MIN, 'max:'.PageTextStyle::FONT_SIZE_MAX],
            'title_font_family' => ['required', 'string', Rule::in(PageTextStyle::fontNames())],
            'title_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'title_bold' => ['required', Rule::in(['Y', 'N'])],

            'show_subtitle' => ['required', Rule::in(['Y', 'N'])],
            'subtitle_font_size' => ['required', 'integer', 'min:'.PageTextStyle::FONT_SIZE_MIN, 'max:'.PageTextStyle::FONT_SIZE_MAX],
            'subtitle_font_family' => ['required', 'string', Rule::in(PageTextStyle::fontNames())],
            'subtitle_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'subtitle_bold' => ['required', Rule::in(['Y', 'N'])],

            'header_content_align' => ['required', 'string', Rule::in(self::ALIGN_VALUES)],
            'use_container' => ['required', Rule::in(['Y', 'N'])],
            'show_breadcrumb' => ['required', Rule::in(['Y', 'N'])],

            'status' => ['required', Rule::in(['Y', 'N'])],
            'detail' => ['required', 'array'],
        ];

        return $rules + $this->languageDetailRules();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $messages["detail.{$lang}.name.required"] = "กรุณากรอกชื่อเมนู ({$lang})";
        }

        return $messages;
    }

    /**
     * ชื่อเมนู required เฉพาะภาษาหลัก (site.lang_default) — หัวเรื่อง/หัวเรื่องรองไม่บังคับทุกภาษา
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.name"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:150'];
            $rules["detail.{$lang}.title"] = ['nullable', 'string', 'max:500'];
            $rules["detail.{$lang}.subtitle"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }
}
