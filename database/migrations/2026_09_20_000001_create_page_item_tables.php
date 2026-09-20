<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูล Page (หน้าเพจเดี่ยวที่ประกอบหลาย section) — ข้อมูลหน้า (page_item_info + page_item_detail แยกภาษา)
     * และโครงสร้างการแสดงผลแบบ แถว (row) → คอลัมน์ (column) → widget ตาม grid 12 ที่แยกเป็นตารางจริงทุกชั้น
     * (แทนการเก็บเป็น JSON ก้อนเดียวของระบบเดิม เพื่อให้ตรวจ file_id ฯลฯ ได้ตรง ๆ) — แต่ละชั้นมีตารางข้อมูลร่วม
     * + ตาราง *_detail แยกภาษา (PK = id + lang) เหมือน article_item_* (ดู docs/PRD-page.md)
     */
    public function up(): void
    {
        Schema::create('page_item_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intro_image_id')->nullable()->constrained('file_info')->nullOnDelete(); // รูปแทนทั้งหน้า (ใช้เป็นโลโก้ตอนทำ SEO ได้)

            // พื้นหลังของทั้งหน้า
            $table->string('background_color', 20)->nullable(); // hex หรือ transparent
            $table->foreignId('background_image_id')->nullable()->constrained('file_info')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable(); // repeat, no-repeat, repeat-x, repeat-y
            $table->string('background_size', 50)->nullable(); // auto, cover, contain
            $table->string('background_attachment', 50)->nullable(); // scroll, fixed
            $table->string('background_position', 50)->nullable(); // เช่น center, top left, bottom right

            $table->dateTime('layout_updated_at')->nullable(); // บันทึกโครงสร้างล่าสุดเมื่อ
            $table->unsignedBigInteger('layout_updated_by')->nullable(); // sys_user.id (ไม่มี FK)

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

        Schema::create('page_item_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = page_item_info.id
            $table->char('lang', 2);

            $table->string('title', 500)->nullable();
            $table->string('intro_text', 2000)->nullable();

            // SEO / AEO / GEO — ขนาดคอลัมน์เดียวกับ article_item_detail
            $table->string('slug', 250)->nullable();
            $table->string('meta_title', 250)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords', 250)->nullable();
            $table->string('og_title', 250)->nullable();
            $table->string('og_description', 500)->nullable();

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->unique(['lang', 'slug']); // slug unique ต่อภาษา
            $table->foreign('id')->references('id')->on('page_item_info')->cascadeOnDelete();
        });

        Schema::create('page_item_row', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_item_info_id')->constrained('page_item_info')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->char('show_title', 1)->default('N'); // แสดงหัวเรื่องของแถวที่หน้าบ้าน
            $table->char('use_container', 1)->default('Y'); // Y = เนื้อหาอยู่ใน container (จำกัดความกว้าง), N = เต็มความกว้างจอ

            $table->string('background_color', 20)->nullable();
            $table->foreignId('background_image_id')->nullable()->constrained('file_info')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable();
            $table->string('background_size', 50)->nullable();
            $table->string('background_attachment', 50)->nullable();
            $table->string('background_position', 50)->nullable();

            $table->char('status', 1)->default('Y'); // Y = แสดง, N = ซ่อน

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['page_item_info_id', 'sort_order']);
        });

        Schema::create('page_item_row_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = page_item_row.id
            $table->char('lang', 2);

            $table->string('title', 250)->nullable();
            $table->string('intro_text', 2000)->nullable();

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('page_item_row')->cascadeOnDelete();
        });

        Schema::create('page_item_column', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_item_row_id')->constrained('page_item_row')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->char('show_title', 1)->default('N');
            $table->unsignedTinyInteger('column_size')->default(12); // ความกว้างใน grid 12 (1 - 12)

            $table->string('background_color', 20)->nullable();
            $table->foreignId('background_image_id')->nullable()->constrained('file_info')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable();
            $table->string('background_size', 50)->nullable();
            $table->string('background_attachment', 50)->nullable();
            $table->string('background_position', 50)->nullable();

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['page_item_row_id', 'sort_order']);
        });

        Schema::create('page_item_column_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = page_item_column.id
            $table->char('lang', 2);

            $table->string('title', 250)->nullable();
            $table->string('intro_text', 2000)->nullable();

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('page_item_column')->cascadeOnDelete();
        });

        Schema::create('page_item_widget', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_item_column_id')->constrained('page_item_column')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->char('show_title', 1)->default('N');
            $table->string('widget_type', 20); // ประเภท widget (ยังรอกำหนดรายละเอียด — ตอนนี้มี placeholder ประเภทเดียว)
            $table->json('setting')->nullable(); // ตั้งค่าเฉพาะประเภท widget

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['page_item_column_id', 'sort_order']);
        });

        Schema::create('page_item_widget_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = page_item_widget.id
            $table->char('lang', 2);

            $table->string('title', 250)->nullable();
            $table->string('intro_text', 2000)->nullable();

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('page_item_widget')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_item_widget_detail');
        Schema::dropIfExists('page_item_widget');
        Schema::dropIfExists('page_item_column_detail');
        Schema::dropIfExists('page_item_column');
        Schema::dropIfExists('page_item_row_detail');
        Schema::dropIfExists('page_item_row');
        Schema::dropIfExists('page_item_detail');
        Schema::dropIfExists('page_item_info');
    }
};
