<?php

namespace App\Http\Requests\Admin\System\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:250',
                // ห้ามซ้ำกับโฟลเดอร์อื่นของผู้ใช้คนเดียวกันที่ยังไม่ถูกลบ
                Rule::unique('folder_info', 'name')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'คุณมีโฟลเดอร์ชื่อนี้อยู่แล้ว',
        ];
    }
}
