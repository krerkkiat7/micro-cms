<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * part ประเภทวิดีโอรองรับ 2 แหล่ง: ไฟล์ที่อัปโหลด (file_id เดิม) หรือลิงก์ YouTube — เพิ่ม video_type
     * ('file'/'youtube') และ youtube_url ให้ article_item_part_file (ใช้เฉพาะแถวของ part_type = video)
     */
    public function up(): void
    {
        Schema::table('article_item_part_file', function (Blueprint $table) {
            $table->string('video_type', 20)->nullable()->after('cover_image_id'); // file, youtube
            $table->string('youtube_url', 500)->nullable()->after('video_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_item_part_file', function (Blueprint $table) {
            $table->dropColumn(['video_type', 'youtube_url']);
        });
    }
};
