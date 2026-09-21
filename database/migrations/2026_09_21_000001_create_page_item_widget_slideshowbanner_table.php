<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget ของโมดูล Page แยกการตั้งค่าเฉพาะประเภทออกเป็นตารางของตัวเอง `page_item_widget_<ประเภท>` โดย PK = `page_item_widget.id`
     * (1 widget = 1 แถวในตารางประเภทของมัน) แทนคอลัมน์ `setting` (JSON) เดิม — ประเภทอย่าง Custom Text ที่กรอกอะไรก็ได้ไม่ควรไปรวมเป็น
     * JSON ก้อนเดียว; `page_item_widget.widget_type` เก็บชื่อประเภท (เช่น `slideshowbanner`) ไว้ชี้ว่าต้องไปอ่านตารางไหน
     * ประเภทแรกคือ "Slideshow จาก banner" (ภาพเต็มภาพเดียวสไลด์ได้ ข้อมูลจาก banner_item_*)
     */
    public function up(): void
    {
        Schema::create('page_item_widget_slideshowbanner', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // = page_item_widget.id
            $table->foreignId('banner_category_info_id')->nullable()->constrained('banner_category_info')->nullOnDelete(); // หมวดหมู่ banner ที่ดึงมาแสดง (บังคับเลือกที่ validation)
            $table->string('sort_by', 20)->default('publish_desc'); // publish_desc / publish_asc / order_asc / order_desc

            $table->char('show_arrows', 1)->default('Y'); // ลูกศรเลื่อนซ้าย/ขวา
            $table->char('show_dots', 1)->default('Y'); // จุดบอกตำแหน่ง (อยู่ในกรอบภาพด้านล่าง)
            $table->char('autoplay', 1)->default('Y'); // เลื่อนอัตโนมัติ
            $table->unsignedSmallInteger('autoplay_interval')->default(5); // ระยะค้างต่อภาพ (วินาที)
            $table->unsignedSmallInteger('transition_speed')->default(500); // ความเร็วเปลี่ยนภาพ (มิลลิวินาที)
            $table->string('transition_effect', 20)->default('slide'); // slide / fade / zoom
            $table->string('aspect_ratio', 10)->default('16:9'); // 16:9 / 21:9 / 4:3 / 1:1

            $table->char('is_clickable', 1)->default('Y'); // กดลิงก์ที่ banner ได้ (banner ที่ไม่มีลิงก์ก็กดไม่ได้)
            $table->string('link_target', 20)->default('_self'); // _self / _blank

            $table->char('show_title', 1)->default('Y'); // แสดงหัวเรื่องของ banner บนภาพ (div ไม่ใช้ h1/h2)
            $table->char('show_intro_text', 1)->default('N'); // แสดงข้อความเกริ่นนำของ banner บนภาพ
            $table->string('text_align', 10)->default('center'); // left / center / right
            $table->string('text_width', 10)->default('container'); // full = เต็มความกว้าง, container = จำกัดตาม container

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id')->references('id')->on('page_item_widget')->cascadeOnDelete();
        });

        // ค่าตั้งค่าเฉพาะประเภทย้ายไปอยู่ในตารางของแต่ละประเภทแล้ว
        Schema::table('page_item_widget', function (Blueprint $table) {
            $table->dropColumn('setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_item_widget', function (Blueprint $table) {
            $table->json('setting')->nullable()->after('widget_type');
        });

        Schema::dropIfExists('page_item_widget_slideshowbanner');
    }
};
