<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลเมนูหน้าบ้าน (front_menu_info + front_menu_detail แยกภาษา เหมือน article_category_info/detail) —
     * โครงสร้างเป็น tree (parent_id ชี้ตัวเอง) เมนูแต่ละรายการผูกได้กับ 1 ใน: หมวดหมู่บทความ / บทความ / หน้าเพจ /
     * URL ภายนอก / หรือเป็นแค่หัวข้อกลุ่ม (heading, ไม่มีลิงก์ — เป็น parent ได้เท่านั้น) — มีชุดตั้งค่า "หัวเรื่อง
     * ของหน้า" (รูปพื้นหลัง + หัวเรื่อง/หัวเรื่องรองพร้อมสไตล์ตัวอักษร + ตำแหน่งบล็อกข้อความแบบ 9 ทิศ ใช้ value set
     * เดียวกับ background-position ที่ BACKGROUND_POSITION_STYLES ใช้อยู่แล้วในโมดูล Intropage เพื่อเอา picker
     * component มาใช้ซ้ำได้ตรง ๆ) ดู docs/PRD-system-frontmenu.md
     */
    public function up(): void
    {
        Schema::create('front_menu_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('front_menu_info')->nullOnDelete();

            $table->string('menu_type', 20); // none, heading, external, article_category, article_item, page — App\Support\FrontMenuType
            $table->foreignId('target_article_category_id')->nullable()->constrained('article_category_info')->nullOnDelete();
            $table->foreignId('target_article_item_id')->nullable()->constrained('article_item_info')->nullOnDelete();
            $table->foreignId('target_page_item_id')->nullable()->constrained('page_item_info')->nullOnDelete();
            $table->string('url', 500)->nullable(); // menu_type = external
            $table->string('link_target', 10)->default('_self'); // _self, _blank

            $table->char('is_home', 1)->default('N'); // มีได้แถวเดียวทั้งระบบที่เป็น Y (บังคับในโค้ด)

            // หัวเรื่องของหน้าเป้าหมาย (ข้อความอยู่ที่ front_menu_detail — ที่นี่เก็บแค่การแสดงผล/สไตล์ ไม่แยกภาษา)
            $table->char('show_header_image', 1)->default('N');
            $table->foreignId('header_image_id')->nullable()->constrained('file_info')->nullOnDelete();

            $table->char('show_title', 1)->default('Y');
            $table->unsignedSmallInteger('title_font_size')->default(28);
            $table->string('title_font_family', 50)->default('Sarabun');
            $table->string('title_color', 20)->default('#000000');
            $table->char('title_bold', 1)->default('N');

            $table->char('show_subtitle', 1)->default('Y');
            $table->unsignedSmallInteger('subtitle_font_size')->default(16);
            $table->string('subtitle_font_family', 50)->default('Sarabun');
            $table->string('subtitle_color', 20)->default('#000000');
            $table->char('subtitle_bold', 1)->default('N');

            $table->string('header_content_align', 20)->default('center'); // ตำแหน่งบล็อกหัวเรื่อง+หัวเรื่องรอง (9 ทิศ)

            $table->char('use_container', 1)->default('Y'); // Y = จำกัดความกว้างใน container, N = เต็มจอ
            $table->char('show_breadcrumb', 1)->default('Y');

            $table->unsignedInteger('sort_order')->default(0);
            $table->char('status', 1)->default('Y'); // Y = แสดง, N = ซ่อน
            $table->char('is_temp', 1)->default('N'); // Y = ข้อมูลตัวอย่าง/ตั้งต้น (ลบทิ้งภายหลังได้)

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
            $table->index('status');
            $table->index('sort_order');
        });

        Schema::create('front_menu_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = front_menu_info.id
            $table->char('lang', 2);

            $table->string('name', 150); // ชื่อเมนูที่แสดงในนำทาง
            $table->string('title', 500)->nullable(); // ข้อความหัวเรื่องของหน้าเป้าหมาย
            $table->string('subtitle', 500)->nullable(); // ข้อความหัวเรื่องรอง

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('front_menu_info')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('front_menu_detail');
        Schema::dropIfExists('front_menu_info');
    }
};
