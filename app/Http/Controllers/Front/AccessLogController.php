<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\LogFrontAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * keep-alive ของ log_front_access — ยิงจาก navigator.sendBeacon (composables/useAccessHeartbeat.ts) เพื่ออัปเดต last_visited
 * ผู้เข้าชมไม่ได้ login จึงกันด้วย token (ULID เดาไม่ได้) + session_id ปัจจุบัน + แถวต้องไม่เก่ากว่า 1 วัน (แทน user_id ของหลังบ้าน)
 * ยกเว้น CSRF ไว้ใน bootstrap/app.php (sendBeacon ตั้ง header ไม่ได้) + throttle — ตอบ 204 เสมอ (ไม่บอกว่าเจอแถวไหม)
 */
class AccessLogController extends Controller
{
    public function ping(Request $request): Response
    {
        $token = (string) $request->input('token', '');

        if (strlen($token) === 26 && $request->hasSession()) {
            LogFrontAccess::query()
                ->where('token', $token)
                ->where('session_id', $request->session()->getId())
                ->where('created_at', '>=', now()->subDay())
                ->update(['last_visited' => now()]);
        }

        return response()->noContent();
    }
}
