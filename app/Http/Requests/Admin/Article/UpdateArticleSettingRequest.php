<?php

namespace App\Http\Requests\Admin\Article;

use App\Support\ArticleSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleSettingRequest extends FormRequest
{
    /**
     * ทะเบียนคีย์/rules อยู่ที่ ArticleSetting (คีย์ที่ไม่อยู่ในนี้จะหายตอนบันทึก เพราะ controller ลบทั้งกลุ่มแล้ว insert ใหม่)
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ArticleSetting::rules();
    }
}
