<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * การจัดรูปแบบตัวอักษรของหน้า Intropage — ข้อความต้อนรับ (ฟอนต์/ขนาด/สี) และปุ่ม (ฟอนต์/ขนาด)
     * ฟอนต์เลือกจาก App\Support\PageTextStyle::FONTS (ฟอนต์ไทยชุดเดียวกับโมดูล page/template)
     */
    public function up(): void
    {
        Schema::table('intropage_item_info', function (Blueprint $table) {
            $table->string('detail_font_family', 50)->default('Sarabun')->after('vdo_url');        // ฟอนต์ข้อความต้อนรับ
            $table->unsignedSmallInteger('detail_font_size')->default(18)->after('detail_font_family'); // ขนาดฟอนต์ข้อความต้อนรับ (px)
            $table->string('detail_color', 20)->default('#1F2937')->after('detail_font_size');     // สีตัวอักษรข้อความต้อนรับ
            $table->unsignedSmallInteger('button_font_size')->default(16)->after('show_button');    // ขนาดฟอนต์ของปุ่ม (px)
            $table->string('button_font_family', 50)->default('Sarabun')->after('button_font_size'); // ฟอนต์ของปุ่ม
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intropage_item_info', function (Blueprint $table) {
            $table->dropColumn(['detail_font_family', 'detail_font_size', 'detail_color', 'button_font_size', 'button_font_family']);
        });
    }
};
