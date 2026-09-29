<?php

namespace App\Http\Requests\Admin\Contactus;

use App\Support\ContactusSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactusSettingRequest extends FormRequest
{
    /**
     * ทะเบียนคีย์/rules อยู่ที่ ContactusSetting (คีย์ที่ไม่อยู่ในนี้จะหายตอนบันทึก เพราะ controller ลบทั้งกลุ่มแล้ว insert ใหม่)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ContactusSetting::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'map_image_id' => 'รูปแผนที่',
            'latitude' => 'ละติจูด',
            'longitude' => 'ลองจิจูด',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'map_image_id.required_if' => 'กรุณาเลือกรูปแผนที่',
            'latitude.required_if' => 'กรุณากรอกละติจูด',
            'longitude.required_if' => 'กรุณากรอกลองจิจูด',
        ];
    }
}
