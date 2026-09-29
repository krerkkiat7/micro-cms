<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลติดต่อเรา — ดู docs/PRD-contactus.md
     * ตั้งค่าการแสดงผลของหน้าติดต่อเราเก็บใน sys_setting กลุ่ม contactus (App\Support\ContactusSetting) ไม่มีตารางของตัวเอง
     *
     * - contactus_item  ข้อมูลที่ผู้ชมส่งมาจากแบบฟอร์มติดต่อที่หน้าบ้าน + สถานะการดำเนินการ/หมายเหตุของผู้ดูแล
     *   เก็บทุกฟิลด์ของแบบฟอร์มไว้เสมอ (ฟิลด์ที่ตั้งค่าซ่อนไว้ตอนส่ง = null) ข้อความเก็บเป็น plain text เท่านั้น
     */
    public function up(): void
    {
        Schema::create('contactus_item', function (Blueprint $table) {
            $table->id();
            $table->string('fullname', 255); // ชื่อ - นามสกุล (บังคับกรอกเสมอ)
            $table->string('position', 255)->nullable(); // ตำแหน่ง
            $table->string('company', 255)->nullable(); // บริษัท
            $table->string('phone', 50)->nullable(); // เบอร์ติดต่อ
            $table->string('email', 255)->nullable(); // อีเมล
            $table->string('subject', 255)->nullable(); // หัวข้อ
            $table->text('detail')->nullable(); // รายละเอียด

            $table->string('lang', 5); // ภาษาของหน้าที่กรอก
            $table->string('remote_ip', 45)->nullable(); // IP ผู้ส่ง (App\Support\ClientIp)
            $table->string('agent', 500)->nullable(); // User-Agent ผู้ส่ง

            // unread = ยังไม่ได้อ่าน, read = อ่านแล้ว, considering = พิจารณา, done = เสร็จสิ้น
            $table->string('process_status', 20)->default('unread');
            $table->text('note')->nullable(); // บันทึกความเห็น/หมายเหตุของผู้ดูแล
            $table->dateTime('read_at')->nullable(); // เปิดอ่านครั้งแรก
            $table->unsignedBigInteger('read_by')->nullable(); // ผู้เปิดอ่านครั้งแรก (sys_user.id)

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable(); // ส่งจากหน้าบ้าน = null เสมอ
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('process_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactus_item');
    }
};
