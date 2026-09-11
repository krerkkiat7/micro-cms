<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ \App\Support\Setting::siteName() }}</title>

        <!-- Favicon — ใช้ไฟล์ default ใน public/ ไปก่อน
             ภายหลังเมื่อมีระบบจัดการไฟล์ (system.file.manage) ค่อยเปลี่ยนมาอ่านจากการตั้งค่า
             แล้ว fallback มาไฟล์นี้เมื่อยังไม่ได้กำหนด -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sarabun:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        @inertia
    </body>
</html>
