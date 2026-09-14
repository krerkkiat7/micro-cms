<?php

namespace App\Http\Requests\Admin\System\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

/**
 * อัพโหลดทีละไฟล์ — ฝั่ง frontend ยิงหลาย request ขนานกันเพื่อ progress ต่อไฟล์ และให้อัพโหลดไฟล์อื่น
 * สำเร็จต่อได้แม้บางไฟล์ในชุดจะไม่ผ่าน (ต่างจากการ validate เป็น array เดียวที่ล้มทั้งชุด)
 */
class StoreFileUploadRequest extends FormRequest
{
    /**
     * นามสกุลไฟล์ที่เป็น OOXML (zip ข้างใน) — บาง magic database ตรวจ mime ได้แค่ระดับ zip เฉย ๆ
     * จึงต้องยอมรับ mime เหล่านี้เพิ่มเติมสำหรับกลุ่มนามสกุลนี้โดยเฉพาะ ไม่ให้ false-reject ไฟล์จริง
     */
    private const ZIP_BASED_EXTENSIONS = ['docx', 'xlsx', 'pptx'];

    private const ZIP_MIME_FALLBACKS = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(array_keys(config('filemanagement.allowed')))
                    ->max(config('filemanagement.max_size_kb')),
            ],
            'folder_id' => [
                'nullable', 'integer',
                Rule::exists('folder_info', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at')),
            ],
        ];
    }

    /**
     * เช็กเพิ่มว่า mime type จริงของไฟล์ตรงกับนามสกุลที่ระบุ (กันไฟล์เปลี่ยนนามสกุลหลอก)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $file = $this->file('file');

            if (! $file || ! $file->isValid()) {
                return;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $expectedMime = config("filemanagement.allowed.{$extension}");

            if ($expectedMime === null) {
                return; // นามสกุลไม่อยู่ในรายการที่อนุญาต — ถูก rule 'file' จับไปแล้ว
            }

            $actualMime = $file->getMimeType();

            if ($actualMime === $expectedMime) {
                return;
            }

            if (in_array($extension, self::ZIP_BASED_EXTENSIONS, true)
                && in_array($actualMime, self::ZIP_MIME_FALLBACKS, true)) {
                return;
            }

            $validator->errors()->add('file', 'ไฟล์นี้ไม่ตรงกับนามสกุลที่ระบุ');
        });
    }
}
