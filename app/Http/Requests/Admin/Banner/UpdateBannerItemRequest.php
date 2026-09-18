<?php

namespace App\Http\Requests\Admin\Banner;

use App\Http\Requests\Admin\Banner\Concerns\BannerItemValidationRules;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerItemRequest extends FormRequest
{
    use BannerItemValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->commonRules() + $this->languageDetailRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'banner_category_info_id.required' => 'กรุณาเลือกหมวดหมู่',
            'publish_down.after' => 'วันที่ปิดการเผยแพร่ต้องมากกว่าวันที่เผยแพร่',
        ];

        foreach (Setting::selectedLanguages() as $lang) {
            $messages["detail.{$lang}.title.required"] = "กรุณากรอกชื่อ ({$lang})";
        }

        return $messages;
    }
}
