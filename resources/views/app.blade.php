<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ \App\Support\Setting::siteName() }}</title>

        <!-- Favicon — เสิร์ฟผ่าน route app.favicon (App\Http\Controllers\AppAssetController)
             อ่านจากค่าตั้งค่าระบบ (sys_setting: site.favicon_id) ถ้ายังไม่ได้ตั้งค่า จะ fallback ไปที่
             public/favicon.ico ให้เอง -->
        <link rel="icon" type="image/x-icon" href="{{ route('app.favicon') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sarabun:400,500,600,700&display=swap" rel="stylesheet" />

        @php($gaTrackingId = \App\Support\Setting::googleAnalyticsTrackingId())
        {{-- Google Analytics (sys_setting: google_analytics.tracking_id) — เฉพาะหน้าบ้าน ไม่ฝังในหลังบ้าน (/admin) --}}
        @if($gaTrackingId && ! request()->routeIs('admin.*'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaTrackingId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', {!! json_encode($gaTrackingId) !!});
            </script>
        @endif

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        @inertia
    </body>
</html>
