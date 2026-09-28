<?php

namespace App\Http\Requests\Admin\Popup;

use App\Support\PopupSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePopupSettingRequest extends FormRequest
{
    /**
     * ทะเบียนคีย์/rules อยู่ที่ PopupSetting (คีย์ที่ไม่อยู่ในนี้จะหายตอนบันทึก เพราะ controller ลบทั้งกลุ่มแล้ว insert ใหม่)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return PopupSetting::rules();
    }
}
