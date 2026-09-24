<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * หน้า Loading ของ template — แสดง/ซ่อนโลโก้ของไซต์ (site.logo_id) เหนือตัวหมุน/รูป Loading
     */
    public function up(): void
    {
        Schema::table('sys_template', function (Blueprint $table) {
            $table->char('loading_show_logo', 1)->default('N')->after('loading_status')->comment('แสดงโลโก้บนหน้า Loading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sys_template', function (Blueprint $table) {
            $table->dropColumn('loading_show_logo');
        });
    }
};
