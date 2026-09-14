<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * เพิ่มคอลัมน์รูปโปรไฟล์ผู้ใช้งานหลังบ้าน — อ้างอิงไฟล์ที่เลือกจากโมดูลจัดการไฟล์ (file_info)
     * เป็นความสัมพันธ์จริง (ไม่ใช่ audit "ผู้กระทำ") จึงผูก FK จริงแบบเดียวกับ usergroup_id
     * nullOnDelete — ถ้าไฟล์ถูกลบถาวรออกจาก file_info ให้เคลียร์ค่านี้เป็น null แทนบล็อกการลบไฟล์
     */
    public function up(): void
    {
        Schema::table('sys_user', function (Blueprint $table) {
            $table->foreignId('profile_image_id')
                ->nullable()
                ->after('facebook')
                ->constrained('file_info')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sys_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_image_id');
        });
    }
};
