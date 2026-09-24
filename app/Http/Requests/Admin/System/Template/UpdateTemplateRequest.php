<?php

namespace App\Http\Requests\Admin\System\Template;

use App\Models\SysTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'status' => ['required', Rule::in(['Y', 'N'])],
        ];
    }

    /**
     * ต้องมี template ที่ใช้งานอยู่ 1 รายการเสมอ — ปิดรายการที่กำลังใช้งานตรง ๆ ไม่ได้ (ให้เปิดรายการอื่นแทน)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $template = SysTemplate::find($this->route('template'));

            if ($template?->status === 'Y' && $this->input('status') === 'N') {
                $validator->errors()->add('status', 'Template นี้กำลังใช้งานอยู่ — ต้องมี Template ที่ใช้งาน 1 รายการเสมอ ให้เปิดใช้งานรายการอื่นแทน');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ Template',
        ];
    }
}
