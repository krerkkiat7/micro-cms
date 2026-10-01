<?php

namespace App\Console\Commands;

use App\Models\FileInfo;
use App\Support\FileDelivery;
use Illuminate\Console\Command;

/**
 * สร้าง thumbnail ล่วงหน้าให้รูปที่อัปโหลดไว้แล้ว (config filemanagement.pregenerate_thumbnail_sizes) — ใช้ครั้งเดียวหลังอัปเดตระบบ
 * หรือหลังเพิ่มขนาดใน config; รูปที่อัปโหลดใหม่สร้างให้อัตโนมัติอยู่แล้ว (FileController::upload) — ขนาดที่มีอยู่แล้วข้าม
 */
class GenerateThumbnails extends Command
{
    protected $signature = 'files:thumbnails {--id=* : เฉพาะ file_info.id ที่ระบุ (ว่าง = รูปทั้งหมด)}';

    protected $description = 'สร้าง thumbnail ล่วงหน้าให้รูปในโมดูลจัดการไฟล์';

    public function handle(): int
    {
        $created = 0;
        $files = 0;

        $query = FileInfo::query()
            ->where('status', 'Y')
            ->whereIn('extension', ['jpg', 'jpeg', 'png', 'gif', 'webp'])
            ->when($this->option('id') !== [], fn ($q) => $q->whereIn('id', $this->option('id')));

        $bar = $this->output->createProgressBar((clone $query)->count());

        $query->chunkById(100, function ($chunk) use (&$created, &$files, $bar) {
            foreach ($chunk as $file) {
                $created += FileDelivery::pregenerateThumbnails($file);
                $files++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("ตรวจรูป {$files} ไฟล์ — สร้าง thumbnail ใหม่ {$created} ไฟล์");

        return self::SUCCESS;
    }
}
