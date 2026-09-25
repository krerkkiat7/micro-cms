<?php

use App\Support\PageTextStyle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ขนาดตัวอักษร (px) เริ่มต้นของ [หัวเรื่อง, หัวเรื่องรอง, ข้อความเกริ่นนำ] ของแต่ละชั้น (แถว = h2, คอลัมน์ = h3, widget = h4) */
    private const DEFAULT_SIZES = [
        'page_item_row' => [32, 20, 16],
        'page_item_column' => [24, 18, 16],
        'page_item_widget' => [20, 16, 14],
    ];

    /**
     * Run the migrations.
     *
     * เพิ่มหัวเรื่องรอง (subtitle) ในตาราง *_detail ของแถว/คอลัมน์/widget และการจัดรูปแบบตัวอักษรของหัวเรื่อง/หัวเรื่องรอง/
     * ข้อความเกริ่นนำ (ขนาด/ฟอนต์/การจัดตำแหน่ง/สี — ค่าเริ่มต้นฟอนต์ Sarabun, กึ่งกลาง, สีดำ) ลงตารางหลักของแต่ละชั้น
     * และเพิ่มพื้นหลัง (สี/รูป/CSS 4 ค่า) ให้ widget เหมือนแถว/คอลัมน์ (ดู docs/PRD-page.md §2)
     */
    public function up(): void
    {
        foreach (self::DEFAULT_SIZES as $tableName => $sizes) {
            Schema::table($tableName, function (Blueprint $table) use ($sizes) {
                foreach (PageTextStyle::PARTS as $index => $part) {
                    $table->unsignedSmallInteger("{$part}_font_size")->default($sizes[$index]);
                    $table->string("{$part}_font_family", 50)->default(PageTextStyle::DEFAULT_FONT);
                    $table->string("{$part}_align", 10)->default(PageTextStyle::DEFAULT_ALIGN);
                    $table->string("{$part}_color", 20)->default(PageTextStyle::DEFAULT_COLOR);
                }
            });
        }

        foreach (['page_item_row_detail', 'page_item_column_detail', 'page_item_widget_detail'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('subtitle', 250)->nullable()->after('title'); // หัวเรื่องรอง
            });
        }

        Schema::table('page_item_widget', function (Blueprint $table) {
            $table->string('background_color', 20)->nullable();
            $table->foreignId('background_image_id')->nullable()->constrained('file_info')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable();
            $table->string('background_size', 50)->nullable();
            $table->string('background_attachment', 50)->nullable();
            $table->string('background_position', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_item_widget', function (Blueprint $table) {
            $table->dropConstrainedForeignId('background_image_id');
            $table->dropColumn(['background_color', 'background_repeat', 'background_size', 'background_attachment', 'background_position']);
        });

        foreach (['page_item_row_detail', 'page_item_column_detail', 'page_item_widget_detail'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('subtitle');
            });
        }

        foreach (array_keys(self::DEFAULT_SIZES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // เฉพาะคอลัมน์ที่ migration นี้สร้าง (ตัวหนา *_bold มาทีหลังใน 2026_10_03_000002 และถูกลบไปก่อนแล้วตอน rollback)
                $table->dropColumn(array_values(array_filter(PageTextStyle::columns(), fn (string $column) => ! str_ends_with($column, '_bold'))));
            });
        }
    }
};
