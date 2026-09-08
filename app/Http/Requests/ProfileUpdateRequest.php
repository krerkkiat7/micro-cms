<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titlename' => ['nullable', 'string', 'max:30'],
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'line' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                // ห้ามซ้ำกับผู้ใช้ประเภทเดียวกันที่ยังไม่ถูกลบ (ยกเว้นตัวเอง)
                Rule::unique(User::class)
                    ->ignore($this->user()->id)
                    ->where(fn ($query) => $query
                        ->where('user_type', $this->user()->user_type)
                        ->whereNull('deleted_at')),
            ],
        ];
    }
}
