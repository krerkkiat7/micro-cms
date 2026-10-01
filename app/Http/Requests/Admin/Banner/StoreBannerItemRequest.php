<?php

namespace App\Http\Requests\Admin\Banner;

use App\Http\Requests\Admin\Banner\Concerns\BannerItemValidationRules;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBannerItemRequest extends FormRequest
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
            'intro_image_id.required' => 'กรุณาเลือกรูปภาพ',
            'link_type.required' => 'กรุณาเลือกประเภทลิงก์',
            'front_menu_info_id.required' => 'กรุณาเลือกเมนูปลายทาง',
            'front_menu_info_id.exists' => 'เมนูปลายทางต้องเป็นเมนูที่เปิดใช้งานและมีลิงก์ (บทความ / หน้าเพจ / ติดต่อเรา / ลิงค์ภายนอก)',
            'url.required' => 'กรุณากรอกลิงก์ URL ปลายทาง',
            'publish_date.required' => 'กรุณากรอกวันที่เผยแพร่',
            'publish_down.after' => 'วันที่ปิดการเผยแพร่ต้องมากกว่าวันที่เผยแพร่',
        ];

        foreach (Setting::selectedLanguages() as $lang) {
            $messages["detail.{$lang}.title.required"] = "กรุณากรอกชื่อ ({$lang})";
        }

        return $messages;
    }
}
