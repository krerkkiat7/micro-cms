<?php

namespace App\Support\Front;

use Illuminate\Support\Facades\Auth;

/**
 * ผู้ใช้ที่ login อยู่ "ของหน้าบ้าน" — หน้าบ้านกับหลังบ้านแยกการ login กันเด็ดขาด: หลังบ้านใช้ guard `web` (ผู้ใช้ user_type = back)
 * หน้าบ้านใช้ guard `front` (config/auth.php — session คนละคีย์กับ `web`) การ login หลังบ้านจึงไม่ทำให้หน้าบ้านเห็นว่ามีผู้ใช้ login
 * โค้ดฝั่งหน้าบ้านต้องอ่านผู้ใช้ผ่านคลาสนี้เท่านั้น ห้ามใช้ Auth::id() / $request->user() (= guard web ของหลังบ้าน)
 *
 * ตอนนี้หน้าบ้านยังไม่มีระบบ login จึงได้ null เสมอ — เมื่อทำ front-office auth ต้อง login ผ่าน Auth::guard('front') และตรวจ user_type = front
 * (ผู้ใช้ user_type = back login หน้าบ้านไม่ได้) เทียบเคียง LoginRequest::authenticate() ของหลังบ้าน
 */
final class FrontAuth
{
    public const GUARD = 'front';

    public static function id(): ?int
    {
        $user = Auth::guard(self::GUARD)->user();

        return $user !== null && $user->user_type === 'front' ? (int) $user->getAuthIdentifier() : null;
    }
}
