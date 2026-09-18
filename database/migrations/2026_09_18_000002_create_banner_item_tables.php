<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลป้ายโฆษณา (banner) — เพิ่ม schema ของตัวป้ายโฆษณาจริง (banner_item_*) ต่อจากหมวดหมู่ที่มีอยู่แล้ว
     * แยกเป็นข้อมูลร่วม (banner_item_info) + ข้อมูลแยกตามภาษา (banner_item_detail, PK = id+lang) เหมือน
     * หมวดหมู่ แต่ป้ายโฆษณาแสดงผลเป็นรูปภาพเท่านั้น (รูปภาพใช้ร่วมทุกภาษา ไม่แยกเหมือนบทความ) พร้อมลิงก์ที่กด
     * ไปได้ (url + link_target) และช่วงเวลาเผยแพร่ — click_amount เก็บไว้สำหรับนับคลิกลิงก์ในอนาคต ยังไม่มี UI
     * แสดง/แก้ไข (เทียบเคียง article_item_info.view_amount)
     */
    public function up(): void
    {
        Schema::create('banner_item_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_category_info_id')->nullable()->constrained('banner_category_info')->nullOnDelete(); // หมวดหมู่ (ตำแหน่งที่ใช้แสดงผล)
            $table->foreignId('intro_image_id')->nullable()->constrained('file_info')->nullOnDelete(); // รูปภาพป้ายโฆษณา (ใช้ร่วมทุกภาษา)
            $table->string('url', 500)->nullable(); // ลิงก์ที่กดไปได้
            $table->string('link_target', 20)->nullable(); // ประเภทการเปิดหน้าต่าง (_self, _blank)
            $table->dateTime('publish_date')->nullable(); // วันที่เผยแพร่
            $table->dateTime('publish_down')->nullable(); // วันที่ปิดการเผยแพร่
            $table->unsignedInteger('click_amount')->default(0); // จำนวนคลิกลิงก์ทั้งหมด (เก็บไว้ใช้ในอนาคต)
            $table->unsignedInteger('sort_order')->default(0); // ลำดับ
            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด
            $table->char('is_temp', 1)->default('N'); // Y = ข้อมูลตัวอย่าง/ตั้งต้น (ลบทิ้งภายหลังได้)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('sort_order');
        });

        Schema::create('banner_item_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = banner_item_info.id
            $table->char('lang', 2);

            $table->string('title', 500)->nullable();
            $table->string('intro_text', 2000)->nullable();

            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด (ต่อภาษา)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('banner_item_info')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_item_detail');
        Schema::dropIfExists('banner_item_info');
    }
};
