<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

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
        $middleware->validateCsrfTokens(except: [
            'admin/system/backlog/access/ping',
        ]);

        // Breeze ถูกย้ายมาไว้ใต้ /admin — ชี้ redirect ของ middleware auth/guest ไป route admin.*
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
