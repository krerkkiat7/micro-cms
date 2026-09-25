<?php

use App\Support\PageSpacing;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'page_item_row' => 'row',
        'page_item_column' => 'column',
        'page_item_widget' => 'widget',
    ];

    /**
     * Run the migrations.
     *
     * เพิ่มระยะขอบด้านใน (padding — สวิตช์เปิด/ปิด default ปิด + ตัวเลข 4 ด้าน px) ให้แถว/คอลัมน์/widget
     * และระยะห่างระหว่างคอลัมน์ (gap แนวนอน/แนวตั้ง px) ให้แถว (ดู App\Support\PageSpacing, docs/PRD-page.md §2)
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName => $level) {
            Schema::table($tableName, function (Blueprint $table) use ($level) {
                $table->char('use_padding', 1)->default('N'); // Y = เว้นระยะขอบด้านในตามค่าด้านล่าง, N = ไม่เว้น
                foreach (PageSpacing::SIDES as $index => $side) {
                    $table->unsignedSmallInteger("padding_{$side}")->default(PageSpacing::DEFAULT_PADDING[$level][$index]);
                }

                if ($level === 'row') {
                    $table->unsignedSmallInteger('gap_x')->default(PageSpacing::DEFAULT_GAP); // ระยะห่างแนวนอนระหว่างคอลัมน์
                    $table->unsignedSmallInteger('gap_y')->default(PageSpacing::DEFAULT_GAP); // ระยะห่างแนวตั้ง (คอลัมน์ขึ้นบรรทัดใหม่/มือถือ)
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName => $level) {
            Schema::table($tableName, function (Blueprint $table) use ($level) {
                $table->dropColumn(PageSpacing::columns($level));
            });
        }
    }
};
