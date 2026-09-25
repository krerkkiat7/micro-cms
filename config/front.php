<?php

/*
|--------------------------------------------------------------------------
| ตั้งค่าหน้าบ้าน (front-office) — ดู docs/PRD-front.md
|--------------------------------------------------------------------------
*/

return [

    /*
    | อายุ cache ข้อมูลหน้าบ้าน (วินาที) — ข้อมูลทั่วไป (template/เมนู/ตั้งค่าไซต์) และเนื้อหา (หน้าเพจ/บทความ/หมวดหมู่)
    | cache ทั้งหมดถูกล้างทันทีเมื่อบันทึกข้อมูลที่เกี่ยวข้องในหลังบ้าน (App\Support\Front\FrontCache) — TTL นี้เป็นแค่ตาข่ายกันพลาด
    | และเป็นตัวกำหนดว่ารายการที่ "ถึง/หมด" ช่วงเผยแพร่ตามเวลาจะปรากฏ/หายไปช้าสุดกี่วินาที
    */
    'cache' => [
        'shared_ttl' => (int) env('FRONT_CACHE_SHARED_TTL', 3600),
        'content_ttl' => (int) env('FRONT_CACHE_CONTENT_TTL', 300),
        'intropage_ttl' => (int) env('FRONT_CACHE_INTROPAGE_TTL', 60),
    ],

    /*
    | ตัวนับยอดเข้าชม (article_item_view / page_item_view + view_amount) — App\Support\Front\ViewCounter
    |
    | driver:
    |   - redis    = ใส่คิวใน Redis list หลังส่ง response แล้วคำสั่ง `front:flush-views` (schedule ทุกนาที) บันทึกเป็นชุด
    |   - database = insert + increment ตรง ๆ หลังส่ง response (ไม่ต้องมี Redis/cron แต่เขียน DB ทุกครั้งที่มีการเข้าชม)
    |   - auto     = redis ถ้า CACHE_STORE เป็น redis ไม่งั้น database
    | dedupe_minutes: ผู้เข้าชม (session เดิม) เปิดหน้าเดิมซ้ำภายในเวลานี้ไม่นับซ้ำ
    | flush_threshold: คิวยาวเกินจำนวนนี้ให้ flush ทันทีโดยไม่รอ scheduler (กันคิวบวมเมื่อ cron ไม่ทำงาน)
    */
    'views' => [
        'driver' => env('FRONT_VIEW_DRIVER', 'auto'),
        'redis_connection' => env('FRONT_VIEW_REDIS_CONNECTION', 'default'),
        'dedupe_minutes' => (int) env('FRONT_VIEW_DEDUPE_MINUTES', 5),
        'flush_threshold' => (int) env('FRONT_VIEW_FLUSH_THRESHOLD', 1000),
        'batch_size' => 1000,
    ],

    /*
    | ขนาด thumbnail ที่หน้าบ้านขอได้ (กันการสั่ง resize ขนาดแปลก ๆ ไม่จำกัดจนเปลือง disk/CPU)
    */
    'thumbnail_sizes' => [160, 320, 480, 640, 960, 1280, 1600, 1920],

];
