<?php

namespace App\Http\Requests\Admin\Article;

use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleTagRequest extends FormRequest
{
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
            $messages["detail.{$lang}.name.required"] = "กรุณากรอกชื่อ ({$lang})";
        }

        return $messages;
    }

    /**
     * เหมือน StoreArticleTagRequest — ไม่มีฟิลด์ slug ในฟอร์ม จึงไม่ต้องกัน slug ชนตัวเอง
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.name"] = [$lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:100'];
        }

        return $rules;
    }
}
