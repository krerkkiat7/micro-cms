<?php

namespace App\Http\Requests\Admin\System;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titlename' => ['required', 'string', 'max:30'],
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:150',
                // ห้ามซ้ำกับผู้ใช้หลังบ้านที่ยังไม่ถูกลบ (ยกเว้นตัวเอง)
                Rule::unique(User::class)
                    ->ignore($this->route('user'))
                    ->where(fn ($query) => $query
                        ->where('user_type', 'back')
                        ->whereNull('deleted_at')),
            ],
            'mobile' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'line' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:150'],
            'profile_image_id' => [
                'nullable', 'integer',
                // เช็กแค่ว่าไฟล์นี้มีอยู่จริงและยังใช้งานอยู่ — ไม่จำกัดว่าต้องเป็นไฟล์ของใคร
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'usergroup_id' => [
                'required', 'integer',
                // กลุ่มที่ใช้งานอยู่ หรือกลุ่มเดิมของผู้ใช้นี้ (แม้ถูกปิดใช้งานไปแล้ว ก็ยังบันทึกซ้ำได้โดยไม่ต้องเปลี่ยนกลุ่ม)
                Rule::exists('sys_usergroup', 'id')->where(fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where(fn ($w) => $w
                        ->where('status', 'Y')
                        ->orWhere('id', $this->currentUsergroupId()))),
            ],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }

    /**
     * กลุ่มเดิมของผู้ใช้ที่กำลังแก้ไข (null = ไม่พบ/ไม่มีกลุ่ม)
     */
    private function currentUsergroupId(): ?int
    {
        return User::query()->whereKey($this->route('user'))->value('usergroup_id');
    }
}
