<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\Front\FrontErrorPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
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
        // หน้า error ของหน้าบ้าน (404/403/419/429/500/503) แสดงใน layout หน้าบ้านตามภาษาของ URL — หลังบ้าน/JSON ใช้ของ Laravel ตามเดิม
        $exceptions->respond(fn (Response $response, Throwable $e, Request $request) => FrontErrorPage::render($response, $request));
    })->create();
