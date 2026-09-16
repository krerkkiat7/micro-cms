<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * เพิ่ม 2 ฟิลด์ควบคุมการแสดงผลของแต่ละ part ที่หน้าบ้าน:
     * - show_title: แสดงหัวเรื่องของ part นี้ที่หน้าบ้านหรือไม่ (checkbox ในฟอร์ม)
     * - status: แสดง/ซ่อน part นี้ทั้งอัน (ไอคอนรูปตา/ตาขีดทับในฟอร์ม — คนละความหมายกับ soft delete)
     */
    public function up(): void
    {
        Schema::table('article_item_part', function (Blueprint $table) {
            $table->char('show_title', 1)->default('Y')->after('images_display_type');
            $table->char('status', 1)->default('Y')->after('show_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_item_part', function (Blueprint $table) {
            $table->dropColumn(['show_title', 'status']);
        });
    }
};
