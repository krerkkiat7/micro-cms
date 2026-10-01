<?php

namespace App\Http\Requests\Admin\Article;

use App\Http\Requests\Admin\Concerns\OnlyEnabledLanguageDetails;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleTagRequest extends FormRequest
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
            $messages["detail.{$lang}.name.required"] = "กรุณากรอกชื่อ ({$lang})";
            $messages["detail.{$lang}.name.unique"] = "มีแท็กชื่อนี้อยู่แล้ว ({$lang})";
        }

        return $messages;
    }

    /**
     * เหมือน StoreArticleTagRequest แต่ unique ไม่รวมแถวของแท็กนี้เอง (แก้ไขแล้วชื่อเดิมไม่ชนตัวเอง)
     * ไม่มีฟิลด์ slug ในฟอร์ม จึงไม่ต้องกัน slug ชนตัวเอง — ไม่นับแท็กที่ถูกลบไปแล้วเช่นเดียวกับตอนสร้างใหม่
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $tagId = $this->route('tag');
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.name"] = [
                $lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:100',
                Rule::unique('article_tag_detail', 'name')
                    ->where(fn ($query) => $query
                        ->where('lang', $lang)
                        ->whereIn('id', fn ($sub) => $sub->select('id')->from('article_tag_info')->whereNull('deleted_at')))
                    ->ignore($tagId, 'id'),
            ];
        }

        return $rules;
    }
}
