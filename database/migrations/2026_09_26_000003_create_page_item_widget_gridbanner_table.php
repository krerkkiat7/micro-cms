<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget "Grid จาก banner" — โครงเดียวกับ `page_item_widget_gridarticle` แต่ตัดวันที่เผยแพร่/จำนวนเข้าชม/ปุ่มอ่านทั้งหมด/
     * รูปแบบ "แถวที่แสดงวันที่เผยแพร่แทนรูปภาพ" ออก (banner ไม่มีแนวคิด "วันที่เผยแพร่ที่แสดงต่อผู้ชม") จึงไม่มี `_detail` ด้วย
     * (ไม่มีฟิลด์แยกภาษา) — เรียงลำดับได้ทั้งวันที่เผยแพร่และลำดับ (sort_order) ของ banner (ดู ReadsBanners) ลิงก์ของการ์ด = ลิงก์ของ banner
     */
    public function up(): void
    {
        Schema::create('page_item_widget_gridbanner', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // = page_item_widget.id
            // ตั้งชื่อ FK เอง เพราะชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL
            $table->foreignId('banner_category_info_id')->nullable()->constrained('banner_category_info', 'id', 'pi_widget_gridbanner_category_foreign')->nullOnDelete(); // หมวดหมู่ banner (บังคับเลือกที่ validation)
            $table->string('sort_by', 20)->default('publish_desc'); // publish_desc / publish_asc / order_asc / order_desc
            $table->unsignedSmallInteger('max_items')->default(0); // จำนวนที่แสดงสูงสุด (0 = ทั้งหมด)

            $table->string('display_type', 10)->default('card'); // card / row_image (ไม่มี row_date)

            // จำนวนคอลัมน์ต่อแถวตามขนาดหน้าจอ (1 - 6)
            $table->unsignedTinyInteger('per_row_pc')->default(4);
            $table->unsignedTinyInteger('per_row_notebook')->default(3);
            $table->unsignedTinyInteger('per_row_tablet')->default(2);
            $table->unsignedTinyInteger('per_row_mobile')->default(1);

            $table->string('link_target', 20)->default('_self'); // เป้าหมายการเปิดลิงก์ (ใช้ร่วมทั้งรูป/หัวเรื่อง/ข้อความเกริ่นนำ) — ลิงก์ = url ของ banner

            // รูปภาพ (การ์ด / แถวที่มีรูปภาพ)
            $table->char('show_image', 1)->default('Y');
            $table->unsignedTinyInteger('image_width_percent')->default(20); // % ความกว้าง — ใช้เฉพาะแถวที่มีรูปภาพ (5 - 50)
            $table->string('aspect_ratio', 10)->default('16:9'); // 16:9 / 21:9 / 4:3 / 1:1
            $table->string('image_fit', 10)->default('cover'); // cover / contain
            $table->string('image_background', 20)->default('#F3F4F6'); // สีพื้นหลังกรอบรูป (hex หรือ transparent) ใช้เมื่อ image_fit = contain
            $table->char('image_clickable', 1)->default('Y');

            // กล่องของการ์ด (ดู CategoryListWidget::cardBoxFields())
            $table->char('show_border', 1)->default('Y');
            $table->string('border_color', 20)->default('#E5E7EB');
            $table->char('rounded_corners', 1)->default('Y');
            $table->string('item_background', 20)->default('#FFFFFF');

            // หัวเรื่อง
            $table->char('show_title', 1)->default('Y');
            $table->unsignedSmallInteger('title_font_size')->default(18); // px
            $table->char('title_bold', 1)->default('Y');
            $table->string('title_font_family', 50)->default('Sarabun');
            $table->string('title_color', 20)->default('#000000');
            $table->string('title_align', 10)->default('left'); // left / center / right
            $table->char('title_clickable', 1)->default('Y');
            $table->unsignedTinyInteger('title_lines')->default(1); // 1 - 3 บรรทัด เกินตัดด้วย ...

            // ข้อความเกริ่นนำ (ซ่อนเป็นค่าเริ่มต้น)
            $table->char('show_intro_text', 1)->default('N');
            $table->unsignedSmallInteger('intro_text_font_size')->default(14);
            $table->char('intro_text_bold', 1)->default('N');
            $table->string('intro_text_font_family', 50)->default('Sarabun');
            $table->string('intro_text_color', 20)->default('#000000');
            $table->string('intro_text_align', 10)->default('left');
            $table->char('intro_text_clickable', 1)->default('N');
            $table->unsignedTinyInteger('intro_text_lines')->default(2);

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
        Schema::dropIfExists('page_item_widget_gridbanner');
    }
};
