<?php

/*
| Ziggy — กลุ่ม route ที่ส่งให้ JavaScript (@routes ใน app.blade.php)
| หน้าบ้านส่งเฉพาะกลุ่ม `front` (ไม่เปิดเผยรายชื่อ URL หลังบ้านใน HTML ของหน้าเว็บสาธารณะ) หน้าหลังบ้านส่งทั้งหมดตามเดิม
*/
return [
    'groups' => [
        'front' => ['front.*', 'app.*'],
    ],
];
