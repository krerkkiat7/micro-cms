<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsergroupRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                // ห้ามซ้ำกับกลุ่มที่ยังไม่ถูกลบ (ยกเว้นตัวเอง)
                Rule::unique('sys_usergroup', 'name')
                    ->ignore($this->route('usergroup'))
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }
}
