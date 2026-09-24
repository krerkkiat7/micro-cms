<?php

namespace App\Http\Requests\Admin\System\Template;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateCodeRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'custom_css_status' => ['required', Rule::in(['Y', 'N'])],
            'custom_css' => ['nullable', 'string', 'max:1000000'],
            'custom_js_status' => ['required', Rule::in(['Y', 'N'])],
            'custom_js' => ['nullable', 'string', 'max:65000'],
        ];
    }
}
