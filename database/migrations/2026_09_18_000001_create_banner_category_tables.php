<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลป้ายโฆษณา (banner) — เริ่มจากโครงสร้างตารางหมวดหมู่ (banner_category_*) หมวดหมู่มีระดับเดียว
     * (ไม่มีหมวดหมู่ย่อย) แยกเป็นข้อมูลร่วม (banner_category_info) + ข้อมูลแยกตามภาษา
     * (banner_category_detail, PK = id+lang) — แปลง schema เดิม (MSSQL-style) มาเป็น convention
     * ของโปรเจกต์เหมือนโมดูลอื่น (snake_case, timestamps()+softDeletes(), status char(1) 'Y'/'N',
     * audit created_by/updated_by/deleted_by = sys_user.id ไม่มี FK) หมวดหมู่ในโมดูลนี้ทำหน้าที่เป็น
     * "ตำแหน่งที่ใช้แสดงผล" (เช่น ไฮไลท์, หน่วยงานที่เกี่ยวข้อง, อื่น ๆ) จึงไม่มีรูปภาพ/ลำดับ/SEO เหมือนหมวดหมู่บทความ
     */
    public function up(): void
    {
        Schema::create('banner_category_info', function (Blueprint $table) {
            $table->id();
            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด
            $table->char('is_temp', 1)->default('N'); // Y = ข้อมูลตัวอย่าง/ตั้งต้น (ลบทิ้งภายหลังได้)

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('banner_category_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = banner_category_info.id
            $table->char('lang', 2);

            $table->string('title', 250)->nullable();
            $table->string('intro_text', 1000)->nullable();

            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด (ต่อภาษา)

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('banner_category_info')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_category_detail');
        Schema::dropIfExists('banner_category_info');
    }
};
