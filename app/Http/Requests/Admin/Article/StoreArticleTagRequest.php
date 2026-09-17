<?php

namespace App\Http\Requests\Admin\Article;

use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleTagRequest extends FormRequest
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
            $messages["detail.{$lang}.name.unique"] = "มีแท็กชื่อนี้อยู่แล้ว ({$lang})";
        }

        return $messages;
    }

    /**
     * ชื่อแท็กแยกตามภาษา — required เฉพาะภาษาหลัก ไม่มีฟิลด์ slug ในฟอร์ม (หน้าบ้านใช้ชื่อ tag ตรง ๆ)
     * ชื่อห้ามซ้ำภายในภาษาเดียวกัน ไม่ว่าแท็กที่ชื่อซ้ำจะสถานะใช้งานหรือไม่ใช้งานก็ตาม — แต่ไม่นับแท็กที่ถูกลบไปแล้ว
     * (`article_tag_info.deleted_at` ไม่ใช่ null) เพื่อให้ลบแท็กแล้วสร้างชื่อเดิมใหม่ได้ (ตัว `article_tag_detail`
     * เองไม่เคยถูก soft delete พร้อมพาเรนต์ จึงต้องเช็กผ่าน `article_tag_info` แทนที่จะเช็ก deleted_at ของตัวเอง)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function languageDetailRules(): array
    {
        $defaultLang = Setting::defaultLanguage();
        $rules = [];

        foreach (Setting::selectedLanguages() as $lang) {
            $rules["detail.{$lang}.name"] = [
                $lang === $defaultLang ? 'required' : 'nullable', 'string', 'max:100',
                Rule::unique('article_tag_detail', 'name')->where(fn ($query) => $query
                    ->where('lang', $lang)
                    ->whereIn('id', fn ($sub) => $sub->select('id')->from('article_tag_info')->whereNull('deleted_at'))),
            ];
        }

        return $rules;
    }
}
