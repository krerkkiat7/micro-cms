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
                // ต้องเป็นไฟล์ของผู้กระทำเอง (คนที่เลือกไฟล์ผ่าน dialog) ที่ยังใช้งานอยู่และยังไม่ถูกลบ
                Rule::exists('file_info', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'usergroup_id' => [
                'required', 'integer',
                Rule::exists('sys_usergroup', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }
}
