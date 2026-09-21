<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> ตารางตั้งค่าของ widget กลุ่ม Slideshow (จาก banner / จาก article) */
    private const TABLES = ['page_item_widget_slideshowbanner', 'page_item_widget_slideshowarticle'];

    /**
     * Run the migrations.
     *
     * widget กลุ่ม Slideshow เพิ่ม (1) การจัดรูปแบบตัวอักษรของหัวเรื่อง/ข้อความเกริ่นนำที่ซ้อนบนภาพ — ขนาด, ฟอนต์ (ชุดเดียวกับหัวเรื่องของแถว/คอลัมน์),
     * สี (default ขาว เพราะอยู่บนภาพ) ตั้งชื่อคอลัมน์ตาม App\Support\PageTextStyle (`<part>_font_size` ฯลฯ) และ (2) จำนวนที่แสดงสูงสุด
     * (`max_items`, 0 = แสดงทั้งหมด)
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedSmallInteger('max_items')->default(0)->after('sort_by'); // จำนวนที่แสดงสูงสุด (0 = ทั้งหมด)

                $table->unsignedSmallInteger('title_font_size')->default(20)->after('text_width'); // px
                $table->string('title_font_family', 50)->default('Sarabun')->after('title_font_size');
                $table->string('title_color', 20)->default('#FFFFFF')->after('title_font_family');
                $table->unsignedSmallInteger('intro_text_font_size')->default(16)->after('title_color'); // px
                $table->string('intro_text_font_family', 50)->default('Sarabun')->after('intro_text_font_size');
                $table->string('intro_text_color', 20)->default('#FFFFFF')->after('intro_text_font_family');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'max_items', 'title_font_size', 'title_font_family', 'title_color',
                    'intro_text_font_size', 'intro_text_font_family', 'intro_text_color',
                ]);
            });
        }
    }
};
