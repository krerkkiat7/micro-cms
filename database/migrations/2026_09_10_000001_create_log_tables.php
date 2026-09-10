<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ไฟล์กลางของตาราง log ทั้งหมด (หลังบ้าน + หน้าบ้าน) — เก็บ log แยก 3 ประเภท
     * ต่อฝั่ง: access (การเข้าชม/เข้าถึงหน้า), action (การกระทำ สร้าง/แก้/ลบ), login (เข้า/ออกระบบ)
     *
     * ตารางถัดไป (`log_back_action`, `log_back_login`, `log_front_access`,
     * `log_front_action`, `log_front_login`) ให้ `Schema::create` เพิ่มในไฟล์นี้ ไม่แยกไฟล์
     *
     * โครงคอลัมน์เดินตาม convention ของตาราง sys_*: `status` char(1) 'Y'/'N',
     * softDeletes, บล็อก audit created_by/updated_by/deleted_by (sys_user.id — เช็กในโค้ด ไม่มี FK)
     */
    public function up(): void
    {
        // log_back_access — การเข้าชม/เข้าถึงหน้าในระบบหลังบ้าน (1 request = 1 แถว)
        Schema::create('log_back_access', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();   // ผู้ใช้งาน (sys_user.id — เช็กในโค้ด ไม่มี FK)
            $table->string('token', 26)->nullable()->unique();   // โทเคนสาธารณะ (ULID) — ใช้อ้างอิงตอน ping อัปเดต last_visited โดยไม่เปิดเผย id
            $table->string('session_id', 100)->nullable();       // session id
            $table->text('uri_string')->nullable();              // URI ที่เข้าถึง
            $table->string('title_name', 255)->nullable();       // ชื่อหน้า / ชื่อ route
            $table->string('remote_ip', 45)->nullable();         // IP address (รองรับ IPv6)
            $table->string('geo_ip', 10)->nullable();            // รหัสประเทศจาก IP
            $table->string('geo_ip_city', 250)->nullable();      // เมืองจาก IP
            $table->string('browser', 50)->nullable();           // เบราว์เซอร์
            $table->string('browser_version', 50)->nullable();   // เวอร์ชันเบราว์เซอร์
            $table->string('mobile', 50)->nullable();            // ชื่อรุ่นอุปกรณ์พกพา (ถ้าตรวจได้)
            $table->string('device_type', 50)->nullable();       // ประเภทอุปกรณ์ (desktop / tablet / mobile)
            $table->string('robot', 50)->nullable();             // ชื่อบอท (ถ้าเป็น bot)
            $table->string('platform', 50)->nullable();          // ระบบปฏิบัติการ
            $table->string('referrer', 250)->nullable();         // อ้างอิงจาก URL
            $table->string('agent', 255)->nullable();            // User-Agent ดิบ (ตัดที่ 255)
            $table->string('accept_lang', 50)->nullable();       // Accept-Language
            $table->string('accept_charset', 50)->nullable();    // Accept-Charset
            $table->date('action_date')->nullable();             // วันที่เข้าถึง — ไว้กรอง / สรุปรายวัน
            $table->dateTime('last_visited')->nullable();        // เวลาที่อยู่หน้านี้ล่าสุด (keep-alive) — ครั้งแรก = created_at

            $table->char('status', 1)->default('Y');             // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable(); // ผู้สร้าง
            $table->unsignedBigInteger('updated_by')->nullable(); // ผู้แก้ไขล่าสุด
            $table->unsignedBigInteger('deleted_by')->nullable(); // ผู้ลบ

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('action_date');
            $table->index('created_at');
        });

        // log_back_login — การเข้า/ออกระบบหลังบ้าน (login/logout, สำเร็จ/ไม่สำเร็จ/ถูกบล็อก)
        Schema::create('log_back_login', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable(); // ผู้ใช้ (sys_user.id ไม่มี FK) — เก็บเมื่อสำเร็จ / logout
            $table->string('log_type', 10)->nullable();        // login / logout
            $table->string('username', 150)->nullable();       // อีเมลที่กรอกตอนเข้าสู่ระบบ
            $table->string('result', 10)->nullable();          // success / fail / block
            $table->string('note', 1000)->nullable();          // เหตุผล (สำเร็จ / รหัสผิด / ไม่พบบัญชี / ถูกระงับ ฯลฯ)
            $table->string('remote_ip', 45)->nullable();       // IP address (รองรับ IPv6)
            $table->date('action_date')->nullable();           // วันที่บันทึก — model เติมอัตโนมัติ

            $table->char('status', 1)->default('Y');           // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable(); // ผู้สร้าง
            $table->unsignedBigInteger('updated_by')->nullable(); // ผู้แก้ไขล่าสุด
            $table->unsignedBigInteger('deleted_by')->nullable(); // ผู้ลบ

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('username');
            $table->index('log_type');
            $table->index('result');
            $table->index('created_at');
        });

        // log_back_action — การกระทำในระบบหลังบ้าน (create / view / update / delete บนข้อมูล)
        Schema::create('log_back_action', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();   // ผู้กระทำ (sys_user.id — เช็กในโค้ด ไม่มี FK)
            $table->string('module_code', 50)->nullable();       // รหัสโมดูล เช่น system.user, system.usergroup.rights
            $table->string('action_type', 50)->nullable();       // ประเภทการกระทำ เช่น create / view / update / delete
            $table->string('value_string', 500)->nullable();     // ชื่อข้อมูลที่ถูกกระทำ (เช่น ชื่อ-นามสกุลผู้ใช้)
            $table->unsignedBigInteger('ref_id')->nullable();    // id ของข้อมูลที่ถูกกระทำ
            $table->date('action_date')->nullable();             // วันที่บันทึก — model เติมอัตโนมัติ
            $table->string('remote_ip', 45)->nullable();         // IP address (รองรับ IPv6)
            $table->string('geo_ip', 10)->nullable();            // รหัสประเทศจาก IP (ยังไม่มี resolver)

            $table->char('status', 1)->default('Y');             // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable(); // ผู้สร้าง
            $table->unsignedBigInteger('updated_by')->nullable(); // ผู้แก้ไขล่าสุด
            $table->unsignedBigInteger('deleted_by')->nullable(); // ผู้ลบ

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('module_code');
            $table->index('action_type');
            $table->index('action_date');
            $table->index('created_at');
            $table->index(['module_code', 'ref_id']); // ประวัติของข้อมูลชิ้นเดียว
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_back_action');
        Schema::dropIfExists('log_back_login');
        Schema::dropIfExists('log_back_access');
    }
};
