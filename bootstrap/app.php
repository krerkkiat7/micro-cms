<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\Admin\AdminErrorPage;
use App\Support\ErrorReference;
use App\Support\Front\FrontErrorPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
        $middleware->alias([
            'setLocale' => SetLocale::class,
        ]);

        // ยกเว้น CSRF เฉพาะ endpoint keep-alive ของ log_back_access — ยิงจาก navigator.sendBeacon
        // ซึ่งตั้ง header ไม่ได้; กันด้วย auth (session) + scope user_id ในตัว controller แทน
        // + keep-alive ของ log_front_access (กันด้วย token + session_id ใน Front\AccessLogController)
        $middleware->validateCsrfTokens(except: [
            'admin/system/backlog/access/ping',
            'front/access/ping',
            'front/banner/click',
        ]);

        // Breeze ถูกย้ายมาไว้ใต้ /admin — ชี้ redirect ของ middleware auth/guest ไป route admin.*
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ข้อมูลประกอบของ exception ที่ถูก report (log หลัก) — รหัสอ้างอิงเดียวกับที่แสดงบนหน้า error 5xx + URL/ผู้ใช้/IP/ชื่อฟิลด์ (ไม่เก็บค่า)
        $exceptions->context(fn () => ErrorReference::context());

        // เขียนซ้ำลง channel `error` (รายวัน เก็บ 90 วัน — config/logging.php) ให้มีประวัติ error เสมอแม้ LOG_STACK ของเครื่องเป็น single
        $exceptions->report(function (Throwable $e) {
            try {
                Log::channel('error')->error($e::class.': '.$e->getMessage(), [
                    ...ErrorReference::context(),
                    'file' => $e->getFile().':'.$e->getLine(),
                    'trace' => mb_substr($e->getTraceAsString(), 0, 8000),
                ]);
            } catch (Throwable) {
                // เขียน log ไม่ได้ (disk เต็ม/สิทธิ์ไฟล์) — ไม่ให้การ log ทำให้หน้า error พังซ้ำ
            }
        });

        // หน้า error: หลังบ้าน (/admin) และหน้าบ้าน (แยกตามภาษาของ URL) — JSON/ไฟล์ใช้ของ Laravel;
        // สร้างไม่ได้ = หน้าสำรอง resources/views/errors/{4xx,5xx}.blade.php
        $exceptions->respond(fn (Response $response, Throwable $e, Request $request) => AdminErrorPage::isAdminRequest($request)
            ? AdminErrorPage::render($response, $request)
            : FrontErrorPage::render($response, $request));
    })->create();
