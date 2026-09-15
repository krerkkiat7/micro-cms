<?php

namespace App\Http\Requests\Admin\Article;

use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleCategoryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'intro_image_id' => [
                'nullable', 'integer',
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
        }

        return $messages;
    }

    /**
     * เหมือน StoreArticleCategoryRequest แต่ slug unique ไม่รวมแถวของหมวดหมู่นี้เอง (แก้ไขแล้วสลักเดิมไม่ชนตัวเอง)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $categoryId = $this->route('category');
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.title"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.intro_text"] = ['nullable', 'string', 'max:2000'];
            $rules["detail.{$lang}.detail"] = ['nullable', 'string'];
            $rules["detail.{$lang}.slug"] = [
                'nullable', 'string', 'max:250',
                Rule::unique('article_category_detail', 'slug')
                    ->where(fn ($query) => $query->where('lang', $lang))
                    ->ignore($categoryId, 'id'),
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
