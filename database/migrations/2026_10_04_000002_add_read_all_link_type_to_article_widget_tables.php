<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ตาราง => ชื่อ FK (ตั้งเอง — ชื่ออัตโนมัติยาวเกิน 64 ตัวอักษรของ MySQL) */
    private const TABLES = [
        'page_item_widget_slidesetarticle' => 'pi_w_slidesetarticle_read_all_menu_fk',
        'page_item_widget_gridarticle' => 'pi_w_gridarticle_read_all_menu_fk',
    ];

    /**
     * Run the migrations.
     *
     * ปุ่ม "อ่านทั้งหมด" (Slideset / Grid จาก article): ประเภทลิงก์ปลายทาง `menu` (เลือกจากเมนูหน้าบ้าน — default) / `custom` (กรอก URL เอง)
     * + เมนูที่เลือก — ข้อมูลเดิมที่กรอก URL ไว้แล้วตั้งเป็น custom (ทำงานเหมือนเดิม) ดู HasReadAllButton
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName => $fkName) {
            Schema::table($tableName, function (Blueprint $table) use ($fkName) {
                $table->string('read_all_link_type', 10)->default('menu')->after('read_all_background'); // menu / custom
                $table->unsignedBigInteger('read_all_menu_id')->nullable()->after('read_all_link_type'); // front_menu_info.id
                $table->foreign('read_all_menu_id', $fkName)->references('id')->on('front_menu_info')->nullOnDelete();
            });

            DB::table($tableName)->whereNotNull('read_all_url')->where('read_all_url', '<>', '')->update(['read_all_link_type' => 'custom']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName => $fkName) {
            Schema::table($tableName, function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
                $table->dropColumn(['read_all_link_type', 'read_all_menu_id']);
            });
        }
    }
};
