<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget Slideset (จาก article / จาก banner) เพิ่มการตั้งค่ากล่องของการ์ด — แสดงเส้นขอบ / มุมมน (default `Y` ทั้งคู่)
     * และ Slideset จาก article เพิ่มตัวอักษรของปุ่ม "อ่านทั้งหมด": ขนาด, ฟอนต์, สีตัวอักษร, สีพื้นหลัง (ใช้กับแบบปุ่ม/ปุ่มมนใหญ่)
     */
    public function up(): void
    {
        foreach (['page_item_widget_slidesetarticle', 'page_item_widget_slidesetbanner'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->char('show_border', 1)->default('Y');
                $table->char('rounded_corners', 1)->default('Y');
            });
        }

        Schema::table('page_item_widget_slidesetarticle', function (Blueprint $table) {
            $table->unsignedSmallInteger('read_all_font_size')->default(14);
            $table->string('read_all_font_family', 50)->default('Sarabun');
            $table->string('read_all_color', 20)->default('#FFFFFF'); // สีตัวอักษร
            $table->string('read_all_background', 20)->default('#1F2937'); // สีพื้นหลัง (แบบปุ่ม / ปุ่มมนใหญ่)
        });

        // ปุ่มแบบลิงก์ข้อความที่มีอยู่แล้วเดิมเป็นสีน้ำเงิน — คงหน้าตาเดิมไว้ (ตัวหนังสือขาวบนพื้นขาวจะมองไม่เห็น)
        DB::table('page_item_widget_slidesetarticle')->where('read_all_style', 'link')->update(['read_all_color' => '#2563EB']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_item_widget_slidesetarticle', function (Blueprint $table) {
            $table->dropColumn(['read_all_font_size', 'read_all_font_family', 'read_all_color', 'read_all_background']);
        });

        foreach (['page_item_widget_slidesetarticle', 'page_item_widget_slidesetbanner'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['show_border', 'rounded_corners']);
            });
        }
    }
};
