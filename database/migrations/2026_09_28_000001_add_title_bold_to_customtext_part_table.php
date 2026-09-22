<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * เพิ่ม "ตัวหนา" ให้การจัดรูปแบบหัวเรื่องของแต่ละ part ใน widget Custom Text (เดิมมีแค่ขนาด/ฟอนต์/จัดตำแหน่ง/สี)
     * ให้ครบชุดเดียวกับหัวเรื่อง/ข้อความเกริ่นนำของ Slideset/Grid (`<part>_bold`) default = ตัวหนา เหมือนหัวเรื่องอื่น ๆ ในระบบ
     */
    public function up(): void
    {
        Schema::table('page_item_widget_customtext_part', function (Blueprint $table) {
            $table->char('title_bold', 1)->default('Y')->after('title_font_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_item_widget_customtext_part', function (Blueprint $table) {
            $table->dropColumn('title_bold');
        });
    }
};
