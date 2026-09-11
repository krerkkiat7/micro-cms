<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoginBackSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recaptcha_enabled' => ['required', Rule::in(['Y', 'N'])],
            'lockout_enabled' => ['required', Rule::in(['Y', 'N'])],
            'lockout_count' => ['nullable', 'required_if:lockout_enabled,Y', 'integer', 'min:1'],
        ];
    }
}
