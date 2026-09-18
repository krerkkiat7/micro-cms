<?php

namespace App\Http\Requests\Admin\Intropage;

use App\Http\Requests\Admin\Intropage\Concerns\IntropageItemValidationRules;
use App\Support\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateIntropageItemRequest extends FormRequest
{
    use IntropageItemValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->commonRules() + $this->languageDetailRules() + $this->buttonRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->ensureSingleHomeButton($validator);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'display_type.required' => 'กรุณาเลือกประเภทการแสดงผล',
            'display_size.required' => 'กรุณาเลือกขนาดการแสดงผล',
            'image_file_id.required_if' => 'กรุณาเลือกรูปภาพ',
            'vdo_file_id.required_if' => 'กรุณาเลือกไฟล์วิดีโอ',
            'vdo_url.required_if' => 'กรุณากรอก URL วิดีโอ',
            'publish_date.required' => 'กรุณากรอกวันที่ประกาศ',
            'publish_down.required' => 'กรุณากรอกวันที่ปิดประกาศ',
            'publish_down.after' => 'วันที่ปิดประกาศต้องมากกว่าวันที่ประกาศ',
            'buttons.min' => 'ต้องมีปุ่มอย่างน้อย 1 ปุ่ม',
        ];

        foreach (Setting::selectedLanguages() as $lang) {
            $messages["detail.{$lang}.title.required"] = "กรุณากรอกชื่อ ({$lang})";
        }

        return $messages;
    }
}
