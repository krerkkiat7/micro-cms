<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * เมนูหลังบ้าน (admin sidebar) — เก็บใน DB แทนการฮาร์ดโค้ดใน AppSidebar.vue
     * โครงสร้าง 2 ระดับ: sys_menu_group (กลุ่ม) → sys_menu (เมนูย่อย/ลิงก์ไปโมดูล)
     */
    public function up(): void
    {
        Schema::create('sys_menu_group', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name', 100);                       // ชื่อกลุ่มที่แสดง
            $table->unsignedInteger('sort_order')->default(0);  // ลำดับการแสดงผล
            $table->char('status', 1)->default('Y');            // Y = แสดง, N = ซ่อน
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sys_menu', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('menu_group_id', 20);               // กลุ่มที่สังกัด
            $table->string('name', 100);                       // ชื่อเมนูที่แสดง
            $table->string('route_name', 100)->nullable();     // ชื่อ route เพื่อลิงก์ไปโมดูล
            $table->unsignedInteger('sort_order')->default(0); // ลำดับการแสดงผล
            $table->string('action_code', 100)->nullable();    // sys_action.code — เงื่อนไขแสดงเมนู (เช็กในโค้ด, ไม่มี FK)
            $table->char('status', 1)->default('Y');           // Y = แสดง, N = ซ่อน
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_group_id')->references('id')->on('sys_menu_group')->cascadeOnDelete();
            $table->index(['menu_group_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_menu');
        Schema::dropIfExists('sys_menu_group');
    }
};
