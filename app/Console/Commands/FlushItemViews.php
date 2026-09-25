<?php

namespace App\Console\Commands;

use App\Support\Front\ViewCounter;
use Illuminate\Console\Command;

/**
 * บันทึกคิวการเข้าชม (Redis) ลง article_item_view / page_item_view แบบ batch + บวก view_amount — schedule ทุกนาทีใน routes/console.php
 * (ต้องมี cron `* * * * * php artisan schedule:run` ที่ server) โหมด database ไม่มีคิว คำสั่งนี้ไม่ทำอะไร
 */
class FlushItemViews extends Command
{
    protected $signature = 'front:flush-views {--type= : article หรือ page (ว่าง = ทุกประเภท)}';

    protected $description = 'บันทึกคิวยอดเข้าชมบทความ/หน้าเพจลงฐานข้อมูลเป็นชุด';

    public function handle(ViewCounter $counter): int
    {
        if (! $counter->buffered()) {
            $this->info('ตัวนับยอดเข้าชมอยู่ในโหมด database (บันทึกตรง) — ไม่มีคิวให้บันทึก');

            return self::SUCCESS;
        }

        $type = $this->option('type') ?: null;

        if ($type !== null && ! isset(ViewCounter::TYPES[$type])) {
            $this->error('ประเภทต้องเป็น article หรือ page');

            return self::INVALID;
        }

        $written = $counter->flush($type);
        $this->info("บันทึกยอดเข้าชม {$written} รายการ");

        return self::SUCCESS;
    }
}
