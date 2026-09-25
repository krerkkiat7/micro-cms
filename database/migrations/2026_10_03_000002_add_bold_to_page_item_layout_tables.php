<?php

use App\Support\PageTextStyle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['page_item_row', 'page_item_column', 'page_item_widget'];

    /**
     * Run the migrations.
     *
     * เพิ่มตัวหนา (Y/N) ให้การจัดรูปแบบตัวอักษรของหัวเรื่อง/หัวเรื่องรอง/ข้อความเกริ่นนำ ของแถว/คอลัมน์/widget
     * — ค่าเริ่มต้นหัวเรื่องหนา อีก 2 ส่วนไม่หนา ตรงกับที่แสดงผลมาก่อนมีตัวเลือกนี้ (ดู App\Support\PageTextStyle::DEFAULT_BOLD)
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                foreach (PageTextStyle::DEFAULT_BOLD as $part => $default) {
                    $table->char("{$part}_bold", 1)->default($default);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(array_map(fn (string $part) => "{$part}_bold", array_keys(PageTextStyle::DEFAULT_BOLD)));
            });
        }
    }
};
