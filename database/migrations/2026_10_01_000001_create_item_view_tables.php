<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ประวัติการเข้าชมรายละเอียดบทความ / หน้าเพจ (1 การเข้าชมที่นับ = 1 แถว) + ยอดรวม `view_amount` บนตาราง info
     *
     * ออกแบบเพื่อ performance (ระบบเดิม insert → select count → update ทุกครั้งที่มีคนเข้า ทำให้เว็บหน่วงทั้งไซต์):
     * - ไม่เขียนตารางนี้ระหว่าง request — App\Support\Front\ViewCounter ใส่คิว (Redis) หลังส่ง response แล้ว
     *   คำสั่ง `front:flush-views` บันทึกเป็นชุด (batch insert 1 ครั้ง + update view_amount 1 ครั้งต่อชุด)
     * - ไม่มี FK ไปตาราง info (insert เร็ว ไม่ต้องล็อก/ตรวจแถวแม่) — แถวของบทความ/หน้าที่ถูกลบค้างไว้เป็นประวัติได้
     * - ไม่ต้อง count() ตารางนี้เพื่อหายอดรวม — view_amount บวกเพิ่มจากจำนวนในชุดตรง ๆ
     *
     * คอลัมน์ตาม convention ของตาราง sys_*: status char(1), audit created_by/updated_by/deleted_by (ไม่มี FK), timestamps, softDeletes
     */
    public function up(): void
    {
        Schema::create('article_item_view', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('article_item_info_id');     // บทความ (article_item_info.id — ไม่มี FK เพื่อให้ insert เร็ว)
            $table->unsignedBigInteger('user_id')->nullable();       // ผู้ใช้งาน (sys_user.id — ถ้า login อยู่)
            $table->string('lang', 10)->nullable();                  // ภาษาที่เข้าชม
            $table->string('session_id', 100)->nullable();           // session id
            $table->string('remote_ip', 45)->nullable();             // IP address (รองรับ IPv6)
            $table->string('geo_ip', 10)->nullable();                // รหัสประเทศจาก IP
            $table->date('action_date')->nullable();                 // วันที่เข้าชม — ไว้สรุปรายวัน

            $table->char('status', 1)->default('Y');                 // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();    // ผู้สร้าง
            $table->unsignedBigInteger('updated_by')->nullable();    // ผู้แก้ไขล่าสุด
            $table->unsignedBigInteger('deleted_by')->nullable();    // ผู้ลบ

            $table->timestamps();                                    // created_at = เวลาที่เข้าชมจริง (ไม่ใช่เวลาที่ flush)
            $table->softDeletes();

            $table->index(['article_item_info_id', 'action_date'], 'article_item_view_item_date_idx');
            $table->index('action_date');
        });

        Schema::create('page_item_view', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('page_item_info_id');         // หน้าเพจ (page_item_info.id — ไม่มี FK เพื่อให้ insert เร็ว)
            $table->unsignedBigInteger('user_id')->nullable();       // ผู้ใช้งาน (sys_user.id — ถ้า login อยู่)
            $table->string('lang', 10)->nullable();                  // ภาษาที่เข้าชม
            $table->string('session_id', 100)->nullable();           // session id
            $table->string('remote_ip', 45)->nullable();             // IP address (รองรับ IPv6)
            $table->string('geo_ip', 10)->nullable();                // รหัสประเทศจาก IP
            $table->date('action_date')->nullable();                 // วันที่เข้าชม — ไว้สรุปรายวัน

            $table->char('status', 1)->default('Y');                 // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();    // ผู้สร้าง
            $table->unsignedBigInteger('updated_by')->nullable();    // ผู้แก้ไขล่าสุด
            $table->unsignedBigInteger('deleted_by')->nullable();    // ผู้ลบ

            $table->timestamps();                                    // created_at = เวลาที่เข้าชมจริง (ไม่ใช่เวลาที่ flush)
            $table->softDeletes();

            $table->index(['page_item_info_id', 'action_date'], 'page_item_view_item_date_idx');
            $table->index('action_date');
        });

        // ยอดเข้าชมรวมของหน้าเพจ (เหมือน article_item_info.view_amount)
        Schema::table('page_item_info', function (Blueprint $table) {
            $table->unsignedInteger('view_amount')->default(0)->after('layout_updated_at'); // เข้าดูทั้งหมด
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_item_info', function (Blueprint $table) {
            $table->dropColumn('view_amount');
        });

        Schema::dropIfExists('page_item_view');
        Schema::dropIfExists('article_item_view');
    }
};
