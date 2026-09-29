<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ตารางประวัติที่เขียนผ่าน App\Support\Front\ViewCounter::write() ตัวเดียวกัน — เพิ่มคอลัมน์พร้อมกันทั้งชุด */
    private const TABLES = ['article_item_view', 'page_item_view', 'banner_item_click'];

    /**
     * Run the migrations.
     *
     * ข้อมูลฝั่งผู้เข้าชม (parse จาก User-Agent ด้วย App\Support\UserAgentParser + header Referer)
     * ไว้ทำรายงานแยกตามอุปกรณ์ / เบราว์เซอร์ / ระบบปฏิบัติการ / แหล่งที่มา — แถวที่บันทึกก่อนหน้านี้เป็น null (= ไม่ทราบ)
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('browser', 50)->nullable()->after('geo_ip');       // เบราว์เซอร์
                $table->string('platform', 50)->nullable()->after('browser');     // ระบบปฏิบัติการ
                $table->string('device_type', 20)->nullable()->after('platform'); // desktop / tablet / mobile
                $table->string('referrer', 250)->nullable()->after('device_type'); // URL ที่อ้างอิงมา
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
                $table->dropColumn(['browser', 'platform', 'device_type', 'referrer']);
            });
        }
    }
};
