<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * index เพิ่มจากการทวนสอบ performance (phase 1) — ตั้งชื่อ index เองให้สั้น (MySQL จำกัด 64 ตัวอักษร)
 * - log_back_login.action_date: สถิติ/รายงานการเข้าสู่ระบบกรองตาม action_date (ตารางอื่นใน log มี index นี้อยู่แล้ว)
 * - log_front_access(action_date, robot): สถิติหน้าบ้านกรองช่วงวัน + แยกบอท/ผู้เข้าชมจริง
 * - article_tag_detail(lang, name): หน้าแท็กหน้าบ้าน /{lang}/article/tag/{ชื่อแท็ก} ค้นแท็กด้วยชื่อ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_back_login', function (Blueprint $table) {
            $table->index('action_date', 'log_back_login_action_date_idx');
        });

        Schema::table('log_front_access', function (Blueprint $table) {
            $table->index(['action_date', 'robot'], 'log_front_access_date_robot_idx');
        });

        Schema::table('article_tag_detail', function (Blueprint $table) {
            $table->index(['lang', 'name'], 'article_tag_detail_lang_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('article_tag_detail', function (Blueprint $table) {
            $table->dropIndex('article_tag_detail_lang_name_idx');
        });

        Schema::table('log_front_access', function (Blueprint $table) {
            $table->dropIndex('log_front_access_date_robot_idx');
        });

        Schema::table('log_back_login', function (Blueprint $table) {
            $table->dropIndex('log_back_login_action_date_idx');
        });
    }
};
