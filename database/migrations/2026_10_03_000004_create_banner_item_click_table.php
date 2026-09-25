<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ประวัติการคลิกลิงก์ของ banner ที่หน้าบ้าน (Slideshow / Slideset / Grid จาก banner — 1 การคลิกที่นับ = 1 แถว) + ยอดรวมบน
     * `banner_item_info.click_amount` (มีอยู่แล้ว) — โครงเดียวกับ article_item_view / page_item_view และบันทึกผ่าน
     * App\Support\Front\ViewCounter ประเภท `banner` (คิว Redis + `front:flush-views` batch insert/update, ข้ามบอท, ไม่นับซ้ำใน session)
     * แทนตาราง banner_item_click ของระบบเดิม (ActionDate/BannerItemID/SessionID/RemoteIP/UserID ไม่มี PK)
     */
    public function up(): void
    {
        Schema::create('banner_item_click', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('banner_item_info_id');      // banner (banner_item_info.id — ไม่มี FK เพื่อให้ insert เร็ว)
            $table->unsignedBigInteger('user_id')->nullable();       // ผู้ใช้งาน (sys_user.id — ถ้า login อยู่)
            $table->string('lang', 10)->nullable();                  // ภาษาของหน้าที่คลิก
            $table->string('session_id', 100)->nullable();           // session id
            $table->string('remote_ip', 45)->nullable();             // IP address (รองรับ IPv6)
            $table->string('geo_ip', 10)->nullable();                // รหัสประเทศจาก IP
            $table->date('action_date')->nullable();                 // วันที่คลิก — ไว้สรุปรายวัน

            $table->char('status', 1)->default('Y');                 // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();                                    // created_at = เวลาที่คลิกจริง (ไม่ใช่เวลาที่ flush)
            $table->softDeletes();

            $table->index(['banner_item_info_id', 'action_date'], 'banner_item_click_item_date_idx');
            $table->index('action_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_item_click');
    }
};
