<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoogleAnalyticsSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Measurement ID (เช่น G-XXXXXXXXXX) หรือ Tracking ID แบบเก่า (UA-XXXXXXX-X) — ไม่ตรวจรูปแบบตายตัว
            // เผื่อ Google เปลี่ยนรูปแบบในอนาคต เทียบเคียงฟิลด์ key ของ Turnstile ที่ไม่ตรวจรูปแบบเช่นกัน
            'tracking_id' => ['nullable', 'string', 'max:50'],
        ];
    }
}
