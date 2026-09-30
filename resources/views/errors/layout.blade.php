{{--
    หน้า error สำรอง — ใช้เมื่อสร้างหน้า error แบบ Inertia ไม่ได้ (ฐานข้อมูลล่ม / ยังไม่ build frontend) หรือ path ที่ไม่ใช่หน้าเว็บ
    CSS inline ทั้งหมด ไม่พึ่ง Vite/ฐานข้อมูล — ข้อมูลจาก App\Support\ErrorFallbackPage (แยกหลังบ้าน/หน้าบ้าน + ภาษา)
    5xx แสดงแค่ว่าเกิดข้อผิดพลาด + รหัสอ้างอิง ไม่แสดงสาเหตุจริง
--}}
@php($error = \App\Support\ErrorFallbackPage::data($status))
<!DOCTYPE html>
<html lang="{{ $error['lang'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title>{{ $error['title'] }} | {{ $error['siteName'] }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 48px 16px;
            background: #f9fafb; color: #111827; font-family: 'Sarabun', system-ui, -apple-system, 'Segoe UI', sans-serif; line-height: 1.6; }
        .card { width: 100%; max-width: 32rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 40px 24px; text-align: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
        .brand { display: inline-flex; flex-direction: column; align-items: center; gap: 12px; color: #111827; text-decoration: none; }
        .brand img { height: 56px; width: auto; max-width: 12rem; object-fit: contain; }
        .brand span { font-size: 1.125rem; font-weight: 600; }
        .status { margin: 32px 0 0; font-size: 3.75rem; font-weight: 700; color: #1d4ed8; line-height: 1; }
        h1 { margin: 12px 0 0; font-size: 1.5rem; }
        .description { margin: 12px 0 0; color: #4b5563; }
        .button { display: inline-block; margin-top: 32px; padding: 10px 20px; border-radius: 8px; background: #1d4ed8; color: #fff; font-weight: 500; text-decoration: none; }
        .button:hover, .button:focus { background: #1e40af; }
        .reference { margin-top: 32px; padding: 12px 16px; border-radius: 8px; background: #f3f4f6; font-size: .875rem; color: #4b5563; }
        .reference code { font-weight: 700; color: #111827; }
        .reference small { display: block; margin-top: 4px; color: #6b7280; }
    </style>
</head>
<body>
    <main class="card">
        <a class="brand" href="{{ $error['homeUrl'] }}">
            <img src="{{ $error['logoUrl'] }}" alt="" onerror="this.remove()">
            <span>{{ $error['siteName'] }}</span>
        </a>

        <p class="status" aria-hidden="true">{{ $error['status'] }}</p>
        <h1>{{ $error['title'] }}</h1>
        <p class="description">{{ $error['description'] }}</p>

        <a class="button" href="{{ $error['homeUrl'] }}">{{ $error['homeLabel'] }}</a>

        @if($error['reference'])
            <div class="reference">
                {{ $error['referenceLabel'] }}: <code>{{ $error['reference'] }}</code>
                <small>{{ $error['referenceHint'] }}</small>
            </div>
        @endif
    </main>
</body>
</html>
