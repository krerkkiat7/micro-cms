<?php

namespace App\Http\Requests\Admin\Banner\Concerns;

use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * กฎ validation ที่ใช้ร่วมกันระหว่าง Store/UpdateBannerItemRequest — เทียบเคียง ArticleItemValidationRules
 * แต่สั้นกว่ามากเพราะไม่มี SEO/slug และไม่มีเนื้อหาแบบแบ่ง part
 */
trait BannerItemValidationRules
{
    /**
     * กฎของฟิลด์ข้อมูลร่วม (ไม่แยกภาษา)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function commonRules(): array
    {
        return [
            'banner_category_info_id' => [
                'required', 'integer',
                Rule::exists('banner_category_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'intro_image_id' => [
                'required', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'url' => ['nullable', 'string', 'max:500'],
            'link_target' => ['nullable', Rule::in(['_self', '_blank'])],
            'publish_date' => ['required', 'date'],
            'publish_down' => ['nullable', 'date', 'after:publish_date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }

    /**
     * กฎของฟิลด์แยกภาษา (detail.<lang>.*) — required เฉพาะ "ชื่อ" ของภาษาหลักเท่านั้น (site.lang_default)
     * ไม่มี slug จึงไม่มีกฎ unique
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.title"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:500'];
            $rules["detail.{$lang}.intro_text"] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
