<?php

namespace App\Http\Requests\Admin\Page;

use App\Http\Requests\Admin\Page\Concerns\PageItemValidationRules;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePageItemRequest extends FormRequest
{
    use PageItemValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->commonRules() + $this->languageDetailRules((int) $this->route('item'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'background_color.regex' => 'รูปแบบสีไม่ถูกต้อง',
        ];

        foreach (Setting::selectedLanguages() as $lang) {
            $messages["detail.{$lang}.title.required"] = "กรุณากรอกชื่อ ({$lang})";
            $messages["detail.{$lang}.slug.unique"] = "Slug นี้ถูกใช้แล้ว ({$lang})";
        }

        return $messages;
    }
}
