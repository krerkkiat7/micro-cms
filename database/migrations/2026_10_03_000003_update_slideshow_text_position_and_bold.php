<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['page_item_widget_slideshowbanner', 'page_item_widget_slideshowarticle'];

    /** ค่าเดิม (แนวนอน — ข้อความอยู่ชิดล่างของภาพเสมอ) → ตำแหน่งใหม่ 9 ตำแหน่งที่หน้าตาเหมือนเดิม */
    private const LEGACY_MAP = ['left' => 'bottom left', 'center' => 'bottom', 'right' => 'bottom right'];

    /**
     * Run the migrations.
     *
     * Slideshow: ตำแหน่งข้อความบนภาพเปลี่ยนจาก ซ้าย/กึ่งกลาง/ขวา (ชิดล่างเสมอ) เป็น 9 ตำแหน่ง (ค่าแบบ CSS background-position,
     * ค่าเริ่มต้นใหม่ = center กึ่งกลางภาพ — ข้อมูลเดิมแปลงให้ชิดล่างเหมือนเดิม) และเพิ่มตัวหนาของหัวเรื่อง (default Y — เดิมแสดงหนาอยู่แล้ว)
     * / ข้อความเกริ่นนำ (default N) — ดู App\Support\PageWidget\SlideshowWidget
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('text_align', 20)->default('center')->change();
                $table->char('title_bold', 1)->default('Y')->after('title_color');
                $table->char('intro_text_bold', 1)->default('N')->after('intro_text_color');
            });

            foreach (self::LEGACY_MAP as $old => $new) {
                DB::table($tableName)->where('text_align', $old)->update(['text_align' => $new]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            // ตำแหน่งใหม่ → ค่าแนวนอนเดิม (ทิ้งแนวตั้ง)
            foreach (['left' => ['top left', 'left', 'bottom left'], 'right' => ['top right', 'right', 'bottom right']] as $old => $news) {
                DB::table($tableName)->whereIn('text_align', $news)->update(['text_align' => $old]);
            }
            DB::table($tableName)->whereNotIn('text_align', ['left', 'right'])->update(['text_align' => 'center']);

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['title_bold', 'intro_text_bold']);
                $table->string('text_align', 10)->default('center')->change();
            });
        }
    }
};
