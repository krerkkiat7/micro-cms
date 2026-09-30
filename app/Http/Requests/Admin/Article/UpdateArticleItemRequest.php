<?php

namespace App\Http\Requests\Admin\Article;

use App\Http\Requests\Admin\Article\Concerns\ArticleItemValidationRules;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleItemRequest extends FormRequest
{
    use ArticleItemValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $itemId = (int) $this->route('item');

        return $this->commonRules($itemId) + $this->languageDetailRules($itemId) + $this->partRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'article_category_info_id.required' => 'กรุณาเลือกหมวดหมู่',
            'publish_date.required' => 'กรุณากรอกวันที่เผยแพร่',
            'publish_down.after' => 'วันที่ปิดการเผยแพร่ต้องมากกว่าวันที่เผยแพร่',
        ];

        foreach (Setting::selectedLanguages() as $lang) {
            $messages["detail.{$lang}.title.required"] = "กรุณากรอกชื่อ ({$lang})";
        }

        return $messages;
    }
}
