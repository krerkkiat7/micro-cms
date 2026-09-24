<?php

namespace App\Http\Requests\Admin\System\Template;

use App\Support\Template\TemplateZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateLoadingRequest extends FormRequest
{
    public const TYPES = ['spinner', 'image'];

    public const SPINNERS = ['ring', 'dots', 'bar'];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loading_status' => ['required', Rule::in(['Y', 'N'])],
            'loading_type' => ['required', Rule::in(self::TYPES)],
            'loading_spinner' => ['required', Rule::in(self::SPINNERS)],
            'loading_color' => ['required', 'string', 'max:20', 'regex:'.TemplateZone::TEXT_COLOR_REGEX],
            'loading_background_color' => ['required', 'string', 'max:20', 'regex:'.TemplateZone::COLOR_REGEX],
            // เลือกรูปเป็นแบบ Loading ต้องมีรูปจริง (ปิดใช้งานอยู่ก็บันทึกค้างไว้ได้)
            'loading_image_id' => [
                Rule::requiredIf(fn () => $this->input('loading_type') === 'image' && $this->input('loading_status') === 'Y'),
                'nullable', 'integer',
                Rule::exists('file_info', 'id')->where(fn ($query) => $query->where('status', 'Y')->whereNull('deleted_at')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'loading_color.regex' => 'รูปแบบสีไม่ถูกต้อง',
            'loading_background_color.regex' => 'รูปแบบสีไม่ถูกต้อง',
            'loading_image_id.required' => 'กรุณาเลือกรูปภาพ Loading',
        ];
    }
}
