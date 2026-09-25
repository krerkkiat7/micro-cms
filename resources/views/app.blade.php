<!DOCTYPE html>
@php
    // หน้าบ้าน = ทุกหน้าที่ไม่ใช่ /admin (รวมหน้า error ของหน้าบ้าน)
    $isAdmin = request()->is('admin', 'admin/*');
    $seo = $isAdmin ? null : ($page['props']['seo'] ?? null);
    $frontAssets = $isAdmin || ! isset($page['props']['front']) ? null : \App\Support\Front\FrontLayoutData::assets();
    $loading = $frontAssets['loading'] ?? null;
    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $seo['fullTitle'] ?? \App\Support\Setting::siteName() }}</title>

        {{-- SEO/AEO/GEO ของหน้าบ้าน — render ฝั่ง server ตั้งแต่ HTML แรก (crawler/บอท AI ที่ไม่รัน JS อ่านได้)
             แท็กที่มี attribute `inertia` ถูกแทนที่ด้วย Components/Front/SeoHead.vue ตอนเปลี่ยนหน้าแบบ Inertia --}}
        @if($seo)
            @if($seo['description'])
                <meta name="description" content="{{ $seo['description'] }}" inertia="description">
            @endif
            @if($seo['keywords'])
                <meta name="keywords" content="{{ $seo['keywords'] }}" inertia="keywords">
            @endif
            <meta name="robots" content="{{ $seo['robots'] }}" inertia="robots">
            <link rel="canonical" href="{{ $seo['canonical'] }}" inertia="canonical">
            @foreach($seo['alternates'] as $alternate)
                <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}" inertia="alternate-{{ $alternate['hreflang'] }}">
            @endforeach
            <meta property="og:type" content="{{ $seo['og']['type'] }}" inertia="og:type">
            <meta property="og:title" content="{{ $seo['og']['title'] }}" inertia="og:title">
            @if($seo['og']['description'])
                <meta property="og:description" content="{{ $seo['og']['description'] }}" inertia="og:description">
            @endif
            <meta property="og:url" content="{{ $seo['og']['url'] }}" inertia="og:url">
            <meta property="og:site_name" content="{{ $seo['og']['site_name'] }}" inertia="og:site_name">
            <meta property="og:locale" content="{{ $seo['og']['locale'] }}" inertia="og:locale">
            @if($seo['og']['image'])
                <meta property="og:image" content="{{ $seo['og']['image'] }}" inertia="og:image">
            @endif
            <meta name="twitter:card" content="{{ $seo['twitter'] }}" inertia="twitter:card">
            <script type="application/ld+json" id="front-jsonld">{!! json_encode($seo['jsonLd'], $jsonFlags) !!}</script>
        @endif

        <!-- Favicon — เสิร์ฟผ่าน route app.favicon (App\Http\Controllers\AppAssetController)
             อ่านจากค่าตั้งค่าระบบ (sys_setting: site.favicon_id) ถ้ายังไม่ได้ตั้งค่า จะ fallback ไปที่
             public/favicon.ico ให้เอง -->
        <link rel="icon" type="image/x-icon" href="{{ route('app.favicon') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sarabun:400,500,600,700&display=swap" rel="stylesheet" />

        @php($gaTrackingId = \App\Support\Setting::googleAnalyticsTrackingId())
        {{-- Google Analytics (sys_setting: google_analytics.tracking_id) — เฉพาะหน้าบ้าน ไม่ฝังในหลังบ้าน (/admin) --}}
        @if($gaTrackingId && ! $isAdmin)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaTrackingId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', {!! json_encode($gaTrackingId) !!});
            </script>
        @endif

        {{-- Custom CSS ของ template ที่เปิดใช้งาน (หน้าบ้านเท่านั้น — ผู้ดูแลที่มีสิทธิ์ system.template.manage เป็นผู้กำหนด) --}}
        @if($frontAssets['custom_css'] ?? null)
            <style id="template-custom-css">{!! str_ireplace('</style', '<\/style', $frontAssets['custom_css']) !!}</style>
        @endif

        @if($loading)
            {{-- หน้า Loading ของ template — แสดงจนกว่าหน้าเว็บโหลดเสร็จ (window load) เฉพาะการโหลดหน้าเต็มครั้งแรก --}}
            <style>
                #front-loading{position:fixed;inset:0;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.25rem;transition:opacity .3s ease}
                #front-loading.is-done{opacity:0;pointer-events:none}
                #front-loading .fl-logo{max-height:4rem;max-width:60vw;object-fit:contain}
                #front-loading .fl-image{max-height:8rem;max-width:70vw;object-fit:contain}
                #front-loading .fl-ring{width:3rem;height:3rem;border-radius:9999px;border:4px solid var(--fl-color);border-top-color:transparent;animation:fl-spin 1s linear infinite}
                #front-loading .fl-dots{display:flex;gap:.5rem}
                #front-loading .fl-dots span{width:.75rem;height:.75rem;border-radius:9999px;background:var(--fl-color);animation:fl-bounce 1s infinite}
                #front-loading .fl-dots span:nth-child(2){animation-delay:.15s}
                #front-loading .fl-dots span:nth-child(3){animation-delay:.3s}
                #front-loading .fl-bar{position:relative;width:10rem;height:.375rem;overflow:hidden;border-radius:9999px;background:#e5e7eb}
                #front-loading .fl-bar span{position:absolute;inset:0 auto 0 0;width:33%;border-radius:9999px;background:var(--fl-color);animation:fl-slide 1.2s ease-in-out infinite}
                @keyframes fl-spin{to{transform:rotate(360deg)}}
                @keyframes fl-bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-50%)}}
                @keyframes fl-slide{0%{left:-33%}100%{left:100%}}
                @media (prefers-reduced-motion:reduce){#front-loading *{animation-duration:3s!important}}
            </style>
            <noscript><style>#front-loading{display:none}</style></noscript>
        @endif

        <!-- Scripts -->
        @routes($isAdmin ? null : 'front')
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        @if($loading)
            <div id="front-loading" role="status" aria-live="polite" style="background-color: {{ $loading['background_color'] }}; --fl-color: {{ $loading['color'] }};">
                <span class="sr-only">{{ __('front.loading') }}</span>
                @if($loading['show_logo'])
                    @if($loading['logo_url'])
                        <img src="{{ $loading['logo_url'] }}" alt="" class="fl-logo">
                    @else
                        <span style="font-size:1.125rem;font-weight:600;color:#374151">{{ \App\Support\Setting::siteName() }}</span>
                    @endif
                @endif
                @if($loading['type'] === 'image' && $loading['image_url'])
                    <img src="{{ $loading['image_url'] }}" alt="" class="fl-image">
                @elseif($loading['spinner'] === 'dots')
                    <span class="fl-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                @elseif($loading['spinner'] === 'bar')
                    <span class="fl-bar" aria-hidden="true"><span></span></span>
                @else
                    <span class="fl-ring" aria-hidden="true"></span>
                @endif
            </div>
            <script>
                window.addEventListener('load', function () {
                    var el = document.getElementById('front-loading');
                    if (!el) return;
                    el.classList.add('is-done');
                    setTimeout(function () { el.remove(); }, 400);
                });
            </script>
        @endif

        @inertia

        {{-- Custom JS ของ template ที่เปิดใช้งาน (หน้าบ้านเท่านั้น) — ทำงานครั้งเดียวตอนโหลดหน้าเต็ม --}}
        @if($frontAssets['custom_js'] ?? null)
            <script id="template-custom-js">{!! str_ireplace('</script', '<\/script', $frontAssets['custom_js']) !!}</script>
        @endif
    </body>
</html>
