<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteSettingRequest extends FormRequest
{
    /** รหัสภาษาที่ระบบรองรับให้เลือกได้ตอนนี้ — เพิ่มภาษาใหม่ในอนาคตแค่เพิ่มในนี้ (ค่าที่เก็บจริงเป็น string เดียวคั่นด้วย , ไม่ผูกจำนวน) */
    private const AVAILABLE_LANGUAGES = ['th', 'en'];

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
            // ไม่ตั้งก็ได้ (ว่าง = ใช้ค่าจาก .env APP_TIMEZONE หรือ php.ini ต่อไป — ดู config/app.php)
            'timezone' => ['nullable', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
            // ภาษาในระบบ — เลือกได้หลายภาษา อย่างน้อย 1 ภาษา (เก็บรวมเป็น 1 record ใน saveGroup())
            'lang_selected' => ['required', 'array', 'min:1'],
            'lang_selected.*' => [Rule::in(self::AVAILABLE_LANGUAGES)],
            // ภาษาหลัก — ต้องเป็นหนึ่งในภาษาที่เลือกไว้ในฟิลด์ข้างบนเท่านั้น
            'lang_default' => ['required', 'string', Rule::in($this->input('lang_selected', []))],
        ];
    }
}
