<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoogleMapSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // API key ของ Google Maps Embed API — ใช้สร้าง iframe แผนที่จากพิกัดในหน้าติดต่อเรา (App\Support\GoogleMap)
            // ไม่ตรวจรูปแบบตายตัว เทียบเคียงฟิลด์ key ของ Turnstile/Google Analytics
            'api_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
