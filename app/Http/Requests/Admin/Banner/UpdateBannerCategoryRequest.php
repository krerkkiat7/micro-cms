<?php

namespace App\Http\Requests\Admin\Banner;

use App\Http\Requests\Admin\Concerns\OnlyEnabledLanguageDetails;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerCategoryRequest extends FormRequest
{
    use OnlyEnabledLanguageDetails;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
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
     * เหมือน StoreBannerCategoryRequest — ไม่มี slug จึงไม่มีอะไรให้ ignore() ตอนแก้ไข
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.title"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:250'];
            $rules["detail.{$lang}.intro_text"] = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }
}
