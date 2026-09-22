<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget Slideset (จาก article / จาก banner) และ Grid จาก article เพิ่ม "สีเส้นขอบของกล่อง" (`border_color`, default
     * `#E5E7EB` เทาอ่อน — สีเดิมที่แสดงอยู่ก่อนเลือกเองได้) และ "สีพื้นหลังของแต่ละรายการ" (`item_background`, default `#FFFFFF`
     * ขาว — เลือก transparent ได้) ทั้งคู่ผ่าน `CategoryListWidget::cardBoxFields()` — Grid จาก article ไม่เคยมีเส้นขอบ/มุมมนมาก่อน
     * จึงต้องเพิ่ม `show_border`/`rounded_corners` ให้ด้วย (Slideset มีอยู่แล้วตั้งแต่ migration `2026_09_25_000001_*`)
     */
    public function up(): void
    {
        foreach (['page_item_widget_slidesetarticle', 'page_item_widget_slidesetbanner'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('border_color', 20)->default('#E5E7EB')->after('show_border');
                $table->string('item_background', 20)->default('#FFFFFF')->after('rounded_corners');
            });
        }

        Schema::table('page_item_widget_gridarticle', function (Blueprint $table) {
            $table->char('show_border', 1)->default('Y')->after('image_clickable');
            $table->string('border_color', 20)->default('#E5E7EB')->after('show_border');
            $table->char('rounded_corners', 1)->default('Y')->after('border_color');
            $table->string('item_background', 20)->default('#FFFFFF')->after('rounded_corners');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_item_widget_gridarticle', function (Blueprint $table) {
            $table->dropColumn(['show_border', 'border_color', 'rounded_corners', 'item_background']);
        });

        foreach (['page_item_widget_slidesetarticle', 'page_item_widget_slidesetbanner'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['border_color', 'item_background']);
            });
        }
    }
};
