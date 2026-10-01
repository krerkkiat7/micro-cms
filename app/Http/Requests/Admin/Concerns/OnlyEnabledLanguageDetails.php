<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Support\Setting;

/**
 * ตัด key ของ `detail` (ข้อมูลแยกภาษา) ที่ไม่ใช่ภาษาที่เปิดใช้งานอยู่ทิ้งก่อน validate — request ที่ประกาศ
 * `'detail' => ['required', 'array']` จะได้ทั้ง array กลับมาใน validated() (รวมภาษาที่ไม่มีกฎ max ฯลฯ กำกับ)
 * เช่น ฟอร์มค้างจากก่อนปิดภาษา หรือ request ที่ถูกแก้เอง → ไม่ให้ภาษาที่ไม่ได้เปิดใช้หลุดไปถึงฐานข้อมูล
 */
trait OnlyEnabledLanguageDetails
{
    protected function prepareForValidation(): void
    {
        $detail = $this->input('detail');

        if (is_array($detail)) {
            $this->merge(['detail' => array_intersect_key($detail, array_flip(Setting::selectedLanguages()))]);
        }
    }
}
