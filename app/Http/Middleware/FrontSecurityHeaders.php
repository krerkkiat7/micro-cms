<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * security header พื้นฐานของหน้าบ้าน — ไม่ใส่ Content-Security-Policy แบบเข้มในรอบนี้ เพราะหน้าบ้านรองรับ Custom JS ของ template,
 * Google Analytics, YouTube embed และฟอนต์จาก Bunny Fonts (ดู roadmap ใน docs/PRD-front.md)
 */
class FrontSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()', false);

        return $response;
    }
}
