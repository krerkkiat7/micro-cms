<?php

namespace App\Http\Requests\Admin\System;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignUsergroupRightsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action_ids' => ['nullable', 'array'],
            'action_ids.*' => ['string', 'distinct', Rule::exists('sys_action', 'id')],
        ];
    }
}
