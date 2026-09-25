<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['page_item_widget_gridarticle', 'page_item_widget_gridbanner'];

    /**
     * Run the migrations.
     *
     * Grid (article / banner) รูปแบบแถว: ตำแหน่งแนวตั้งของส่วนข้อมูล top / center / bottom (default top — ตรงกับที่แสดงมาก่อน
     * ส่วน article แบบ top วันที่/จำนวนเข้าชมอยู่ล่างเสมอ) — ดู CategoryListWidget::CONTENT_ALIGNS
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('content_align', 10)->default('top')->after('display_type'); // top / center / bottom
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
                $table->dropColumn('content_align');
            });
        }
    }
};
