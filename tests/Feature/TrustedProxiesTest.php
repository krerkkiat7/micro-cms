<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;

/**
 * TRUSTED_PROXIES (config app.trusted_proxies) — ระบบหลัง load balancer/ingress ที่ทำ HTTPS ต้องรู้ว่า request จริงเป็น https
 */
beforeEach(function () {
    Route::get('/_test/scheme', fn () => response()->json(['secure' => request()->isSecure(), 'ip' => request()->ip()]));
});

afterEach(function () {
    TrustProxies::flushState();
});

function bootProvidersWithProxies(?string $proxies): void
{
    config(['app.trusted_proxies' => $proxies]);
    TrustProxies::flushState();
    (new AppServiceProvider(app()))->boot();
}

test('forwarded headers are ignored when no proxy is trusted', function () {
    bootProvidersWithProxies(null);

    $this->getJson('/_test/scheme', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
        ->assertJson(['secure' => false, 'ip' => '127.0.0.1']);
});

test('a trusted proxy passes on the https scheme and the client ip', function (string $proxies) {
    bootProvidersWithProxies($proxies);

    $this->getJson('/_test/scheme', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
        ->assertJson(['secure' => true, 'ip' => '203.0.113.9']);
})->with(['*', '10.0.0.0/8, 127.0.0.1']);
