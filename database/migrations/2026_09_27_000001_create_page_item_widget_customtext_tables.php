<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget "Custom Text" — ต่างจากประเภทอื่นที่ตั้งค่าเป็นแถวเดียวต่อ widget (page_item_widget_<ประเภท> PK = page_item_widget.id)
     * เพราะ Custom Text กรอกเนื้อหาเองแบบแบ่ง "part" เรียงลำดับได้หลายรายการต่อ 1 widget เหมือนระบบ part ของบทความ
     * (article_item_part, ดู 2026_09_16_000001_create_article_item_tables) — รองรับ 4 ประเภท: text, image, images, video
     * (ตัดเอกสารออกเพราะ widget นี้เน้นข้อความ/สื่อ ไม่ใช่เอกสารแนบเหมือนบทความ)
     *
     * page_item_widget_customtext_part = 1 แถวต่อ 1 part (คีย์หลักเป็นของตัวเอง ไม่ใช่ id ของ widget เพราะมีได้หลายแถว)
     * + page_item_widget_customtext_part_file (ไฟล์ของ part: รูปภาพ/วิดีโอ, หลายแถวได้เฉพาะ part_type = images)
     * + page_item_widget_customtext_part_detail (หัวข้อ/เนื้อหาข้อความแยกภาษา PK = id + lang)
     *
     * หัวเรื่องของแต่ละ part จัดรูปแบบได้เอง (ขนาด/ฟอนต์/จัดตำแหน่ง/สี — ชุดฟิลด์เดียวกับ App\Support\PageTextStyle แต่เก็บแยกต่อ part
     * ไม่ใช่ต่อ widget) จึงมีคอลัมน์ title_font_size/title_font_family/title_align/title_color อยู่บนตัว part เอง
     */
    public function up(): void
    {
        Schema::create('page_item_widget_customtext_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_item_widget_id')->constrained('page_item_widget')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0); // ลำดับ part ใน widget (ลากสลับ/ย้ายผ่าน dialog เรียงลำดับ)
            $table->string('part_type', 10); // text, image, images, video
            $table->string('images_display_type', 20)->nullable(); // เฉพาะ part_type = images (ค่าเดียวกับ article_item_part)
            $table->char('show_title', 1)->default('Y'); // แสดงหัวเรื่องของ part นี้หรือไม่
            $table->char('status', 1)->default('Y'); // แสดง/ซ่อน part นี้ทั้งอัน (คนละความหมายกับ soft delete)
            $table->json('setting')->nullable(); // ตั้งค่าเพิ่มเติมตามประเภท part (การจัดตำแหน่ง/ขนาด/คอลัมน์ ฯลฯ)

            // การจัดรูปแบบหัวเรื่องของ part นี้ (เทียบเคียง App\Support\PageTextStyle แต่เก็บต่อ part)
            $table->unsignedSmallInteger('title_font_size')->default(20); // px
            $table->string('title_font_family', 50)->default('Sarabun');
            $table->string('title_align', 10)->default('left'); // left / center / right
            $table->string('title_color', 20)->default('#000000');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ตั้งชื่อ index เอง เพราะชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL
            $table->index(['page_item_widget_id', 'sort_order'], 'pi_widget_customtext_part_widget_sort_idx');
        });

        Schema::create('page_item_widget_customtext_part_file', function (Blueprint $table) {
            $table->id();
            // ตั้งชื่อ FK เอง เพราะชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL
            $table->foreignId('page_item_widget_customtext_part_id')
                ->constrained('page_item_widget_customtext_part', 'id', 'pi_widget_customtext_part_file_part_foreign')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0); // ลำดับไฟล์ในกลุ่ม (เฉพาะ part_type = images มีหลายแถว)
            $table->foreignId('file_id')->nullable()->constrained('file_info')->nullOnDelete(); // ไฟล์หลักของแถวนี้ (รูปภาพ/วิดีโอ)
            $table->foreignId('cover_image_id')->nullable()->constrained('file_info')->nullOnDelete(); // รูปภาพหน้าปก (เฉพาะวิดีโอ)
            $table->string('video_type', 20)->nullable(); // file, youtube (เฉพาะ part_type = video)
            $table->string('youtube_url', 500)->nullable();
            $table->json('description')->nullable(); // รายละเอียด/alt_text แยกตามภาษา หรือตั้งค่าไฟล์ (autoplay/controls ของวิดีโอ)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ตั้งชื่อ index เอง เพราะชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL
            $table->index(['page_item_widget_customtext_part_id', 'sort_order'], 'pi_widget_customtext_part_file_part_sort_idx');
        });

        Schema::create('page_item_widget_customtext_part_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = page_item_widget_customtext_part.id
            $table->char('lang', 2);

            $table->string('title', 250)->nullable(); // หัวข้อของ part (ใส่ได้ทุกประเภท)
            $table->text('detail')->nullable(); // เนื้อหาของ part ประเภทข้อความ (rich text)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('page_item_widget_customtext_part')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_item_widget_customtext_part_detail');
        Schema::dropIfExists('page_item_widget_customtext_part_file');
        Schema::dropIfExists('page_item_widget_customtext_part');
    }
};
