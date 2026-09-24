<?php

namespace App\Http\Requests\Admin\System\Template;

use App\Support\Template\TemplatePreset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'preset' => ['required', Rule::in(TemplatePreset::values())],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ Template',
            'preset.required' => 'กรุณาเลือกแม่แบบ',
            'preset.in' => 'แม่แบบไม่ถูกต้อง',
        ];
    }
}
