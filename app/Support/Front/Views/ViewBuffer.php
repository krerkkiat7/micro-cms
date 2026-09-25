<?php

namespace App\Support\Front\Views;

/**
 * คิวพักการเข้าชม (article/page) ก่อนบันทึกลงฐานข้อมูลเป็นชุด — ดู App\Support\Front\ViewCounter
 */
interface ViewBuffer
{
    /**
     * ต่อท้ายคิว 1 รายการ
     *
     * @param  array<string, mixed>  $row
     */
    public function push(string $type, array $row): void;

    /**
     * ดึงออกจากหัวคิวไม่เกิน $max รายการ (ดึงแล้วหายจากคิว — atomic)
     *
     * @return list<array<string, mixed>>
     */
    public function pull(string $type, int $max): array;

    /**
     * ใส่คืนท้ายคิว (กรณีบันทึกลงฐานข้อมูลไม่สำเร็จ)
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function restore(string $type, array $rows): void;

    public function size(string $type): int;
}
