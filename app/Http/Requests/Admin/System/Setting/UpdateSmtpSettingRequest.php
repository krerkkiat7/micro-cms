<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmtpSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'use_auth' => ['required', Rule::in(['Y', 'N'])],
            'username' => ['nullable', 'required_if:use_auth,Y', 'string', 'max:255'],
            'password' => ['nullable', 'required_if:use_auth,Y', 'string', 'max:255'],
            'ssl_type' => ['required', Rule::in(['none', 'ssl', 'tls'])],
            'from_name' => ['nullable', 'string', 'max:150'],
            'from_email' => ['nullable', 'string', 'email', 'max:150'],
        ];
    }
}
