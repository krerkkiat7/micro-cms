<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:150'],
            'site_email' => ['nullable', 'string', 'email', 'max:150'],
            'site_description' => ['nullable', 'string'],
            'logo_id' => [
                'nullable', 'integer',
                // เช็กแค่ว่าไฟล์นี้มีอยู่จริง ยังใช้งานอยู่ และเป็น .png (ตามที่กำหนด) — ไม่จำกัดว่าต้องเป็นไฟล์ของใคร
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->where('extension', 'png')
                    ->whereNull('deleted_at')),
            ],
            'favicon_id' => [
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->where('extension', 'ico')
                    ->whereNull('deleted_at')),
            ],
            'copyright_year' => ['nullable', 'digits:4'],
            'copyright_owner' => ['nullable', 'string', 'max:150'],
        ];
    }
}
