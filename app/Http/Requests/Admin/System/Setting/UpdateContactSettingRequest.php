<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // ที่อยู่แยกภาษา — ชื่อฟิลด์ address_<lang> ต้องตรงกับภาษาที่ระบบรองรับ (เทียบเคียง
            // UpdateSiteSettingRequest::AVAILABLE_LANGUAGES เพราะระบบรองรับแค่ th/en ตอนนี้)
            'address_th' => ['nullable', 'string'],
            'address_en' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
        ];
    }
}
