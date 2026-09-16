<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลบทความ (article) — เพิ่ม schema ของตัวบทความจริง (article_item_*) ต่อจากหมวดหมู่ที่มีอยู่แล้ว
     * แยกเป็นข้อมูลร่วม (article_item_info) + ข้อมูลแยกตามภาษา (article_item_detail, PK = id+lang) เหมือน
     * หมวดหมู่ แต่เนื้อหาเปลี่ยนไปใช้ "part" แทนฟิลด์ detail เดียว (article_item_part + article_item_part_file
     * + article_item_part_detail) เพื่อรองรับเนื้อหาแบบผสม ข้อความ/รูปภาพ/วิดีโอ/เอกสาร เรียงลำดับ/ลากสลับได้
     * และเพิ่มชุดแท็กกลาง (article_tag_info/article_tag_detail) ที่บทความเลือกผูกได้หลายแท็กผ่าน pivot
     * article_item_tag (เทียบเคียง sys_usergroup_action)
     */
    public function up(): void
    {
        Schema::create('article_tag_info', function (Blueprint $table) {
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

        Schema::create('article_tag_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = article_tag_info.id
            $table->char('lang', 2);

            $table->string('name', 100)->nullable();
            $table->string('slug', 100)->nullable();
            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->unique(['lang', 'slug']); // ชื่อแท็กใช้เป็น slug unique ต่อภาษา
            $table->foreign('id')->references('id')->on('article_tag_info')->cascadeOnDelete();
        });

        Schema::create('article_item_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_category_info_id')->nullable()->constrained('article_category_info')->nullOnDelete(); // หมวดหมู่
            $table->foreignId('intro_image_id')->nullable()->constrained('file_info')->nullOnDelete(); // รูปภาพหน้าปก
            $table->dateTime('publish_date')->nullable(); // วันที่เผยแพร่
            $table->dateTime('publish_down')->nullable(); // วันที่ปิดการเผยแพร่
            $table->unsignedInteger('view_amount')->default(0); // เข้าดูทั้งหมด
            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด
            $table->char('is_temp', 1)->default('N'); // Y = ข้อมูลตัวอย่าง/ตั้งต้น (ลบทิ้งภายหลังได้)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('publish_date');
        });

        Schema::create('article_item_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = article_item_info.id
            $table->char('lang', 2);

            $table->string('title', 500)->nullable();
            $table->string('intro_text', 2000)->nullable();
            // เนื้อหาเต็มใช้ระบบ part แทน (article_item_part) ไม่มีฟิลด์ detail เดียวเหมือนหมวดหมู่

            // SEO / AEO / GEO — ขนาดคอลัมน์เดียวกับ article_category_detail
            $table->string('slug', 250)->nullable();
            $table->string('meta_title', 250)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords', 250)->nullable();
            $table->string('og_title', 250)->nullable();
            $table->string('og_description', 500)->nullable();

            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด (ต่อภาษา)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->unique(['lang', 'slug']); // slug unique ต่อภาษา
            $table->foreign('id')->references('id')->on('article_item_info')->cascadeOnDelete();
        });

        Schema::create('article_item_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_item_info_id')->constrained('article_item_info')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0); // ลำดับ part ในบทความ (ลากสลับได้)
            $table->string('part_type', 10); // text, image, images, video, document, documents
            $table->string('images_display_type', 20)->nullable(); // เฉพาะ part_type = images: thumbnail_carousel, multi_carousel, grid_lightbox, full_width_slider, masonry_grid, justified_grid, stacked_cards
            $table->json('setting')->nullable(); // ตั้งค่าเพิ่มเติมตามประเภท part เช่น เปิด/ปิด pdf preview ของเอกสาร

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_item_info_id', 'sort_order']);
        });

        Schema::create('article_item_part_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_item_part_id')->constrained('article_item_part')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0); // ลำดับไฟล์ในกลุ่ม (รูปภาพ/เอกสารหลายไฟล์)
            $table->foreignId('file_id')->nullable()->constrained('file_info')->nullOnDelete(); // ไฟล์หลักของแถวนี้ (รูปภาพ/เอกสาร/วิดีโอ)
            $table->foreignId('cover_image_id')->nullable()->constrained('file_info')->nullOnDelete(); // รูปภาพหน้าปก (สำหรับวิดีโอ)
            $table->json('description')->nullable(); // รายละเอียด/alt_text แยกตามภาษา เช่น {"th": "...", "en": "..."}

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_item_part_id', 'sort_order']);
        });

        Schema::create('article_item_part_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = article_item_part.id
            $table->char('lang', 2);

            $table->string('title', 250)->nullable(); // หัวข้อของ part (ใส่ได้ทุกประเภท ยกเว้นข้อความล้วนที่ไม่มีหัวข้อแยก)
            $table->text('detail')->nullable(); // เนื้อหาของ part ประเภทข้อความ (rich text)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('article_item_part')->cascadeOnDelete();
        });

        Schema::create('article_item_tag', function (Blueprint $table) {
            $table->foreignId('article_item_info_id')->constrained('article_item_info')->cascadeOnDelete();
            $table->foreignId('article_tag_info_id')->constrained('article_tag_info')->cascadeOnDelete();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->primary(['article_item_info_id', 'article_tag_info_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_item_tag');
        Schema::dropIfExists('article_item_part_detail');
        Schema::dropIfExists('article_item_part_file');
        Schema::dropIfExists('article_item_part');
        Schema::dropIfExists('article_item_detail');
        Schema::dropIfExists('article_item_info');
        Schema::dropIfExists('article_tag_detail');
        Schema::dropIfExists('article_tag_info');
    }
};
