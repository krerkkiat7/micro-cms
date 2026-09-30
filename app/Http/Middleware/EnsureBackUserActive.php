<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสถานะผู้ใช้หลังบ้านทุก request (ไม่ใช่แค่ตอน login) — ผู้ใช้ที่ถูกระงับ (status = 'N') ระหว่างที่ยัง login อยู่
 * หรือไม่ใช่ผู้ใช้หลังบ้าน (user_type != 'back') จะถูก logout ทันทีใน request ถัดไป แล้วกลับไปหน้า login พร้อมข้อความ
 * (ผู้ใช้ที่ถูกลบ = soft delete ถูกตัดโดย user provider อยู่แล้ว เพราะ query ไม่รวมแถวที่ถูกลบ)
 */
class EnsureBackUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->status !== 'Y' || $user->user_type !== 'back')) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
            ]);
        }

        return $next($request);
    }
}
