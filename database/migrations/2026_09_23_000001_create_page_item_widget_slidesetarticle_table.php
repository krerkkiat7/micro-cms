<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget "Slideset จาก article" — ตารางตั้งค่าเฉพาะประเภท (PK = `page_item_widget.id`) การ์ดบทความหลายใบที่เลื่อนดูได้: หมวดหมู่/การเรียงลำดับ/
     * จำนวนสูงสุด, carousel (ลูกศร จุด เลื่อนอัตโนมัติ), จำนวนการ์ดต่อแถวตามขนาดหน้าจอ และการแสดง/จัดรูปแบบของแต่ละส่วนของการ์ด
     * (รูป, หัวเรื่อง, ข้อความเกริ่นนำ, วันที่เผยแพร่, จำนวนเข้าชม) ชื่อคอลัมน์ตัวอักษรตาม App\Support\PageTextStyle (`<part>_font_size` ฯลฯ)
     */
    public function up(): void
    {
        Schema::create('page_item_widget_slidesetarticle', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // = page_item_widget.id
            // ตั้งชื่อ FK เอง เพราะชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL
            $table->foreignId('article_category_info_id')->nullable()->constrained('article_category_info', 'id', 'pi_widget_slidesetarticle_category_foreign')->nullOnDelete(); // หมวดหมู่ article (บังคับเลือกที่ validation)
            $table->string('sort_by', 20)->default('publish_desc'); // publish_desc / publish_asc
            $table->unsignedSmallInteger('max_items')->default(0); // จำนวนที่แสดงสูงสุด (0 = ทั้งหมด)

            // carousel
            $table->char('show_arrows', 1)->default('Y');
            $table->char('show_dots', 1)->default('Y'); // จุดอยู่ใต้การ์ด (พื้นที่ด้านล่าง)
            $table->char('autoplay', 1)->default('N');
            $table->unsignedSmallInteger('autoplay_interval')->default(5); // ระยะค้างต่อภาพ (วินาที)
            $table->unsignedSmallInteger('transition_speed')->default(500); // ความเร็วเปลี่ยนภาพ (มิลลิวินาที)

            // จำนวนการ์ดต่อแถวตามขนาดหน้าจอ (1 - 6)
            $table->unsignedTinyInteger('per_row_pc')->default(4);
            $table->unsignedTinyInteger('per_row_notebook')->default(3);
            $table->unsignedTinyInteger('per_row_tablet')->default(2);
            $table->unsignedTinyInteger('per_row_mobile')->default(1);

            // รูปภาพ
            $table->char('show_image', 1)->default('Y');
            $table->string('aspect_ratio', 10)->default('16:9'); // 16:9 / 21:9 / 4:3 / 1:1
            $table->string('image_fit', 10)->default('cover'); // cover / contain
            $table->char('image_clickable', 1)->default('Y');

            // เป้าหมายเปิดลิงก์ (ใช้ร่วมทั้งรูป/หัวเรื่อง/ข้อความเกริ่นนำ)
            $table->string('link_target', 20)->default('_self'); // _self / _blank

            // หัวเรื่อง
            $table->char('show_title', 1)->default('Y');
            $table->unsignedSmallInteger('title_font_size')->default(18); // px
            $table->char('title_bold', 1)->default('Y');
            $table->string('title_font_family', 50)->default('Sarabun');
            $table->string('title_color', 20)->default('#000000');
            $table->string('title_align', 10)->default('left'); // left / center / right
            $table->char('title_clickable', 1)->default('Y');
            $table->unsignedTinyInteger('title_lines')->default(1); // 1 - 3 บรรทัด เกินตัดด้วย ...

            // ข้อความเกริ่นนำ
            $table->char('show_intro_text', 1)->default('Y');
            $table->unsignedSmallInteger('intro_text_font_size')->default(14);
            $table->char('intro_text_bold', 1)->default('N');
            $table->string('intro_text_font_family', 50)->default('Sarabun');
            $table->string('intro_text_color', 20)->default('#000000');
            $table->string('intro_text_align', 10)->default('left');
            $table->char('intro_text_clickable', 1)->default('N');
            $table->unsignedTinyInteger('intro_text_lines')->default(2);

            // วันที่เผยแพร่
            $table->char('show_date', 1)->default('Y');
            $table->unsignedSmallInteger('date_font_size')->default(12);
            $table->char('date_bold', 1)->default('N');
            $table->string('date_font_family', 50)->default('Sarabun');
            $table->string('date_color', 20)->default('#667085'); // เทา

            // จำนวนเข้าชม
            $table->char('show_views', 1)->default('N');
            $table->unsignedSmallInteger('views_font_size')->default(12);
            $table->char('views_bold', 1)->default('N');
            $table->string('views_font_family', 50)->default('Sarabun');
            $table->string('views_color', 20)->default('#667085'); // เทา

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
        Schema::dropIfExists('page_item_widget_slidesetarticle');
    }
};
