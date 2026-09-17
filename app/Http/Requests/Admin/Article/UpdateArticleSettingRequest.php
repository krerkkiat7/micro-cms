<?php

namespace App\Http\Requests\Admin\Article;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // กลุ่ม "รายการบทความ"
            'list_per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'list_display_mode' => ['required', Rule::in(['card', 'row'])],
        ];
    }
}
