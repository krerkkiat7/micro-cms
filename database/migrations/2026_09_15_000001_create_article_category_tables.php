<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลบทความ (article) — เริ่มจากโครงสร้างตารางหมวดหมู่ (article_category_*) หมวดหมู่มีระดับเดียว
     * (ไม่มีหมวดหมู่ย่อย) แยกเป็นข้อมูลร่วม (article_category_info) + ข้อมูลแยกตามภาษา
     * (article_category_detail, PK = id+lang) — แปลง schema เดิม (MSSQL-style) มาเป็น convention
     * ของโปรเจกต์เหมือนโมดูลอื่น (snake_case, timestamps()+softDeletes(), status char(1) 'Y'/'N',
     * audit created_by/updated_by/deleted_by = sys_user.id ไม่มี FK)
     */
    public function up(): void
    {
        Schema::create('article_category_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intro_image_id')->nullable()->constrained('file_info')->nullOnDelete(); // รูปภาพหน้าปก
            $table->unsignedInteger('sort_order')->default(0); // ลำดับ
            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด
            $table->char('is_temp', 1)->default('N'); // Y = ข้อมูลตัวอย่าง/ตั้งต้น (ลบทิ้งภายหลังได้)

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('sort_order');
        });

        Schema::create('article_category_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = article_category_info.id
            $table->char('lang', 2);

            $table->string('title', 250)->nullable();
            $table->string('intro_text', 2000)->nullable();
            $table->text('detail')->nullable(); // คำอธิบาย

            // SEO / AEO / GEO
            $table->string('slug', 250)->nullable();
            $table->string('meta_title', 250)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords', 250)->nullable();
            $table->string('og_title', 250)->nullable();
            $table->string('og_description', 500)->nullable();

            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด (ต่อภาษา)

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->unique(['lang', 'slug']); // slug unique ต่อภาษา
            $table->foreign('id')->references('id')->on('article_category_info')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_category_detail');
        Schema::dropIfExists('article_category_info');
    }
};
