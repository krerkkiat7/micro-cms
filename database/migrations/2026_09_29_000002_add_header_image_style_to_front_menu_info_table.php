<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * เพิ่มอัตราส่วน/ประเภทการแสดงรูปภาพ/สีพื้นหลังของรูปภาพส่วนหัวเมนู — value เหมือนฟิลด์ aspect_ratio/image_fit/
     * image_background ของ widget Slideset/Grid (ดู `App\Support\PageWidget\SettingsWidget`) แต่เพิ่มตัวเลือก
     * 'natural' (ตามขนาดรูปภาพ ไม่ครอป ไม่ต้องตั้ง image_fit/สีพื้นหลัง) ที่ widget เดิมไม่มี
     */
    public function up(): void
    {
        Schema::table('front_menu_info', function (Blueprint $table) {
            $table->string('header_image_aspect_ratio', 10)->default('natural')->after('header_image_id');
            $table->string('header_image_fit', 10)->default('cover')->after('header_image_aspect_ratio');
            $table->string('header_image_background', 20)->default('#F3F4F6')->after('header_image_fit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('front_menu_info', function (Blueprint $table) {
            $table->dropColumn(['header_image_aspect_ratio', 'header_image_fit', 'header_image_background']);
        });
    }
};
