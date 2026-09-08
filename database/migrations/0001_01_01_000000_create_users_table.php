<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('sys_usergroup', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->char('status', 1)->default('Y'); // Y = ใช้งาน, N = ไม่ใช้งาน
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sys_user', function (Blueprint $table) {
            $table->id();
            $table->string('titlename', 30)->nullable(); // คำนำหน้า
            $table->string('firstname', 100);            // ชื่อ
            $table->string('lastname', 100);             // นามสกุล
            // email ไม่ unique ระดับ DB — ตาราง sys_user เดียวใช้ทั้งหน้าบ้าน/หลังบ้าน (user_type)
            // และรองรับ soft delete จึงเช็ก "ห้ามซ้ำกับผู้ใช้ประเภทเดียวกันที่ยังไม่ถูกลบ" ในโค้ดแทน
            $table->string('email', 150)->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('user_type', 20)->default('back');   // back = ผู้จัดการหลังบ้าน, front = หน้าบ้าน

            $table->string('mobile', 20)->nullable();    // เบอร์มือถือ
            $table->string('phone', 30)->nullable();     // เบอร์ติดต่อ
            $table->string('line', 100)->nullable();     // LINE id
            $table->string('facebook', 150)->nullable(); // facebook (url/handle)

            $table->char('status', 1)->default('Y');     // Y = ใช้งาน, N = ไม่ใช้งาน (ถูกระงับ)

            // ประวัติการเข้าสู่ระบบ (ใช้ประกอบการล็อกบัญชีเมื่อ login ผิดหลายครั้ง — เกณฑ์อ่านจาก sys_setting ภายหลัง)
            $table->timestamp('last_login_at')->nullable();              // ครั้งล่าสุดที่ login สำเร็จ
            $table->unsignedSmallInteger('failed_login_count')->default(0); // จำนวนครั้งที่ login ไม่สำเร็จติดต่อกัน
            $table->timestamp('last_failed_login_at')->nullable();       // ครั้งล่าสุดที่ login ไม่สำเร็จ

            $table->foreignId('usergroup_id')->nullable()->constrained('sys_usergroup')->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();

            $table->softDeletes();
        });

        // โทเคนรีเซ็ตรหัสผ่านของผู้ใช้หลังบ้าน (broker 'users')
        // แยกตารางตาม user_type เพราะ sys_user เดียวเก็บทั้ง back/front และ email อาจซ้ำข้ามประเภทได้
        // email เป็น PK ได้เพราะ "email ห้ามซ้ำต่อ user_type ที่ยังไม่ถูกลบ" (บังคับในโค้ด)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // โทเคนรีเซ็ตรหัสผ่านของผู้ใช้หน้าบ้าน (broker 'front') — เผื่อ front-office auth ในอนาคต
        Schema::create('front_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('sys_action_group', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sys_action', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('action_group_id', 20);
            $table->string('parent_id', 20)->nullable(); // สำหรับทำ tree
            $table->string('code', 100)->unique();
            $table->string('name', 150);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('action_group_id')->references('id')->on('sys_action_group')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('sys_action')->nullOnDelete();
        });

        Schema::create('sys_usergroup_action', function (Blueprint $table) {
            $table->foreignId('usergroup_id')->constrained('sys_usergroup')->cascadeOnDelete();
            $table->string('action_id', 20);
            $table->timestamps();
            $table->primary(['usergroup_id', 'action_id']);

            $table->foreign('action_id')->references('id')->on('sys_action')->cascadeOnDelete();
        });

        Schema::create('sys_setting', function (Blueprint $table) {
            $table->string('group', 50);
            $table->string('name', 100);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->primary(['group', 'name']); // composite primary key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_setting');
        Schema::dropIfExists('sys_usergroup_action');
        Schema::dropIfExists('sys_user');
        Schema::dropIfExists('sys_usergroup');
        Schema::dropIfExists('sys_action');
        Schema::dropIfExists('sys_action_group');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('front_password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
