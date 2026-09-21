<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget "Slideshow จาก article" — ตารางตั้งค่าเฉพาะประเภท (PK = `page_item_widget.id`) โครงเดียวกับ page_item_widget_slideshowbanner
     * ต่างที่หมวดหมู่ชี้ไป article_category_info และเรียงลำดับได้เฉพาะตามวันที่เผยแพร่ (บทความไม่มี sort_order ต่อรายการ)
     */
    public function up(): void
    {
        Schema::create('page_item_widget_slideshowarticle', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // = page_item_widget.id
            $table->foreignId('article_category_info_id')->nullable()->constrained('article_category_info', 'id', 'pi_widget_slideshowarticle_category_foreign')->nullOnDelete(); // หมวดหมู่ article ที่ดึงมาแสดง (บังคับเลือกที่ validation) — ตั้งชื่อ FK เอง เพราะชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL
            $table->string('sort_by', 20)->default('publish_desc'); // publish_desc / publish_asc (บทความไม่มี sort_order ต่อรายการ)

            $table->char('show_arrows', 1)->default('Y'); // ลูกศรเลื่อนซ้าย/ขวา
            $table->char('show_dots', 1)->default('Y'); // จุดบอกตำแหน่ง (อยู่ในกรอบภาพด้านล่าง)
            $table->char('autoplay', 1)->default('Y'); // เลื่อนอัตโนมัติ
            $table->unsignedSmallInteger('autoplay_interval')->default(5); // ระยะค้างต่อภาพ (วินาที)
            $table->unsignedSmallInteger('transition_speed')->default(500); // ความเร็วเปลี่ยนภาพ (มิลลิวินาที)
            $table->string('transition_effect', 20)->default('slide'); // slide / fade / zoom
            $table->string('aspect_ratio', 10)->default('16:9'); // 16:9 / 21:9 / 4:3 / 1:1

            $table->char('is_clickable', 1)->default('Y'); // กดลิงก์ไปหน้าบทความได้
            $table->string('link_target', 20)->default('_self'); // _self / _blank

            $table->char('show_title', 1)->default('Y'); // แสดงหัวเรื่องของบทความบนภาพ (div ไม่ใช้ h1/h2)
            $table->char('show_intro_text', 1)->default('N'); // แสดงข้อความเกริ่นนำของบทความบนภาพ
            $table->string('text_align', 10)->default('center'); // left / center / right
            $table->string('text_width', 10)->default('container'); // full = เต็มความกว้าง, container = จำกัดตาม container

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id')->references('id')->on('page_item_widget')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_item_widget_slideshowarticle');
    }
};
