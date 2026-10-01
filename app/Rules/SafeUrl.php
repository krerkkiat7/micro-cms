<?php

namespace App\Rules;

use App\Support\Front\FrontUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ลิงก์ที่หน้าบ้านนำไปแสดงได้จริง — เกณฑ์เดียวกับ FrontUrl::safeExternal() (http(s)://, path ภายใน /..., #anchor, mailto:, tel:)
 * ไม่งั้นค่าที่กรอกผิดรูปแบบ (เช่น www.example.com ไม่มี https://) จะถูกหน้าบ้านตัดทิ้งเงียบ ๆ โดยผู้ดูแลไม่รู้ตัว
 */
class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || FrontUrl::safeExternal($value) === null) {
            $fail('ลิงก์ต้องขึ้นต้นด้วย https:// หรือ http:// (ลิงก์ภายนอก) หรือ / (หน้าภายในเว็บ) เช่น https://www.example.com หรือ /th/contactus');
        }
    }
}
