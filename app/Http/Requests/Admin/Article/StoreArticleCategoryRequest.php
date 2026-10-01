<?php

namespace App\Http\Requests\Admin\Article;

use App\Http\Requests\Admin\Concerns\OnlyEnabledLanguageDetails;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleCategoryRequest extends FormRequest
{
    use OnlyEnabledLanguageDetails;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'intro_image_id' => [
                'nullable', 'integer',
                // เช็กแค่ว่าไฟล์นี้มีอยู่จริงและยังใช้งานอยู่ — ไม่จำกัดว่าต้องเป็นไฟล์ของใคร (เทียบเคียง sys_user.profile_image_id)
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            $messages["detail.{$lang}.title.required"] = "กรุณากรอกชื่อ ({$lang})";
            $messages["detail.{$lang}.slug.not_regex"] = "slug ({$lang}) ต้องไม่เป็นตัวเลขล้วน และห้ามมีเครื่องหมาย /";
        }

        return $messages;
    }

    /**
     * กฎของฟิลด์แยกภาษา (detail.<lang>.*) — required เฉพาะ "ชื่อ" ของภาษาหลักเท่านั้น (site.lang_default)
     * ฟิลด์อื่น/ภาษาอื่นเป็น nullable ทั้งหมด — slug unique เฉพาะภายในภาษาเดียวกัน (article_category_detail)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.title"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.intro_text"] = ['nullable', 'string', 'max:2000'];
            $rules["detail.{$lang}.detail"] = ['nullable', 'string'];
            $rules["detail.{$lang}.slug"] = [
                // ตัวเลขล้วนจะชนกับ URL หน้าบ้าน /{lang}/article/category/{id}/{article_id} และ / ใช้ใน path ไม่ได้
                'nullable', 'string', 'max:250', 'not_regex:/^\d+$|\//',
                // ไม่นับแถว detail ของหมวดหมู่ที่ถูกลบแล้ว (detail ไม่ถูก soft delete ตามพาเรนต์)
                Rule::unique('article_category_detail', 'slug')->where(fn ($query) => $query
                    ->where('lang', $lang)
                    ->whereIn('id', fn ($sub) => $sub->select('id')->from('article_category_info')->whereNull('deleted_at'))),
            ];
            $rules["detail.{$lang}.meta_title"] = ['nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.meta_description"] = ['nullable', 'string', 'max:500'];
            $rules["detail.{$lang}.meta_keywords"] = ['nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.og_title"] = ['nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.og_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }
}
