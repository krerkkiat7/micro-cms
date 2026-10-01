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
     * ลิงก์ของป้ายโฆษณา: เลือกประเภทก่อน (แบบเดียวกับปุ่ม "อ่านทั้งหมด" ของ widget) — `none` ไม่มีลิงก์ / `menu` เลือกจากเมนูหน้าบ้าน /
     * `custom` กรอก URL เอง (url + link_target เดิม) — ข้อมูลเดิมที่กรอก URL ไว้แล้วตั้งเป็น custom (ทำงานเหมือนเดิม) ดู BannerItemInfo::LINK_TYPES
     */
    public function up(): void
    {
        Schema::table('banner_item_info', function (Blueprint $table) {
            $table->string('link_type', 10)->default('none')->after('intro_image_id'); // none / menu / custom
            $table->foreignId('front_menu_info_id')->nullable()->after('link_type')
                ->constrained('front_menu_info', 'id', 'banner_item_info_front_menu_fk')->nullOnDelete(); // เมนูปลายทาง (link_type = menu)
        });

        DB::table('banner_item_info')->whereNotNull('url')->where('url', '<>', '')->update(['link_type' => 'custom']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_item_info', function (Blueprint $table) {
            $table->dropForeign('banner_item_info_front_menu_fk');
            $table->dropColumn(['link_type', 'front_menu_info_id']);
        });
    }
};
