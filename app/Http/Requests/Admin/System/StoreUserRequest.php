<?php

namespace App\Http\Requests\Admin\System;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
                // ห้ามซ้ำกับผู้ใช้หลังบ้าน (user_type = back) ที่ยังไม่ถูกลบ
                Rule::unique(User::class)->where(fn ($query) => $query
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
                Rule::exists('sys_usergroup', 'id')->where(fn ($query) => $query
                    ->where('status', 'Y')
                    ->whereNull('deleted_at')),
            ],
            'password' => [
                'required', 'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }
}
