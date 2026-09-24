<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// บันทึกคิวยอดเข้าชมบทความ/หน้าเพจ (Redis) ลงฐานข้อมูลเป็นชุด — ต้องตั้ง cron `* * * * * php artisan schedule:run`
// (ดู docs/PRD-front.md §ยอดเข้าชม) โหมด database ไม่มีคิว คำสั่งจบทันที
Schedule::command('front:flush-views')->everyMinute()->withoutOverlapping();
