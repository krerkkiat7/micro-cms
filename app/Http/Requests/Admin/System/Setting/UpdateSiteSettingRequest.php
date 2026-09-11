<?php

namespace App\Http\Requests\Admin\System\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:150'],
            'site_email' => ['nullable', 'string', 'email', 'max:150'],
            'site_description' => ['nullable', 'string'],
            'copyright_year' => ['nullable', 'digits:4'],
            'copyright_owner' => ['nullable', 'string', 'max:150'],
        ];
    }
}
