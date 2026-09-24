<?php

namespace App\Http\Requests\Admin\System\Template;

use App\Support\Template\TemplateZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * บันทึกตั้งค่าทั้ง 4 โซน (header / body / footer / aside) พร้อมกันจากหน้าโครงสร้าง — กฎมาจาก TemplateZone::fields()
 */
class UpdateTemplateLayoutRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (TemplateZone::ZONES as $zone) {
            $rules[$zone] = ['required', 'array'];
            $rules += TemplateZone::rules($zone, "{$zone}.");
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.*.regex' => 'รูปแบบสีไม่ถูกต้อง',
            '*.*.in' => 'ค่าที่เลือกไม่ถูกต้อง',
            '*.*.required' => 'กรุณาระบุค่าให้ครบ',
        ];
    }
}
