<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget "Grid จาก article" — เพิ่มตัวอักษรของ "กล่องวันที่เผยแพร่" (ใช้แทนพื้นที่รูปภาพทั้งหมดในรูปแบบ `row_date`) แยกเป็นเลขวัน
     * (`date_day_*` — ตัวใหญ่ ตัวหนา เทาเข้ม) กับเดือน/ปี (`date_month_*` — ตัวเล็ก เทาอ่อน) คนละส่วนกัน เพราะแสดงคนละบรรทัด/ขนาดกัน
     * และสีพื้นหลังของกล่อง (`date_box_background`, default เทาจาง `#F3F4F6` เดียวกับสีพื้นหลังกรอบรูปแบบ contain) ดู GridArticleWidget::dateBoxFields()
     */
    public function up(): void
    {
        Schema::table('page_item_widget_gridarticle', function (Blueprint $table) {
            $table->unsignedSmallInteger('date_day_font_size')->default(18);
            $table->char('date_day_bold', 1)->default('Y');
            $table->string('date_day_font_family', 50)->default('Sarabun');
            $table->string('date_day_color', 20)->default('#374151'); // เทาเข้ม (gray-700)

            $table->unsignedSmallInteger('date_month_font_size')->default(11);
            $table->char('date_month_bold', 1)->default('N');
            $table->string('date_month_font_family', 50)->default('Sarabun');
            $table->string('date_month_color', 20)->default('#9CA3AF'); // เทาอ่อน (gray-400)

            $table->string('date_box_background', 20)->default('#F3F4F6'); // เทาจาง
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_item_widget_gridarticle', function (Blueprint $table) {
            $table->dropColumn([
                'date_day_font_size', 'date_day_bold', 'date_day_font_family', 'date_day_color',
                'date_month_font_size', 'date_month_bold', 'date_month_font_family', 'date_month_color',
                'date_box_background',
            ]);
        });
    }
};
