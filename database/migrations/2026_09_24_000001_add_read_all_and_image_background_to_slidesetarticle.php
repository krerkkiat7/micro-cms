<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * widget "Slideset จาก article" เพิ่ม (1) สีพื้นหลังของกรอบรูปเมื่อแสดงแบบ contain (default เทาอ่อน `#F3F4F6`, เลือก transparent ได้)
     * และ (2) ปุ่ม "อ่านทั้งหมด": แสดง/ตำแหน่ง/ไอคอน+ตำแหน่งไอคอน/รูปแบบ/ลิงก์ปลายทาง/เป้าหมายลิงก์ ในตารางตั้งค่า
     * ส่วนข้อความของปุ่ม (แยกภาษา) อยู่ในตาราง `page_item_widget_slidesetarticle_detail` (PK = id + lang เหมือนโมดูลอื่น)
     */
    public function up(): void
    {
        Schema::table('page_item_widget_slidesetarticle', function (Blueprint $table) {
            $table->string('image_background', 20)->default('#F3F4F6')->after('image_fit'); // สีพื้นหลังกรอบรูป (hex หรือ transparent) ใช้เมื่อ image_fit = contain

            $table->char('show_read_all', 1)->default('N');
            $table->string('read_all_position', 20)->default('bottom_center'); // top_left / top_center / top_right / bottom_left / bottom_center / bottom_right
            $table->string('read_all_icon', 30)->default('arrow_right'); // none / plus / plus_circle / arrow_right / arrow_right_circle / chevron_right / chevron_right_circle / arrow_up_right
            $table->string('read_all_icon_position', 10)->default('after'); // before / after
            $table->string('read_all_style', 10)->default('button'); // button / link / pill
            $table->string('read_all_url', 500)->nullable(); // ลิงก์ปลายทาง (ภายหลังอาจเลือกจากเมนูหน้าบ้านได้)
            $table->string('read_all_link_target', 20)->default('_self'); // _self / _blank
        });

        Schema::create('page_item_widget_slidesetarticle_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = page_item_widget_slidesetarticle.id (= page_item_widget.id)
            $table->char('lang', 2);

            $table->string('read_all_text', 100)->nullable(); // ข้อความแทน "อ่านทั้งหมด" (ว่าง = ใช้ข้อความมาตรฐานของหน้าบ้าน)

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('page_item_widget_slidesetarticle')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_item_widget_slidesetarticle_detail');

        Schema::table('page_item_widget_slidesetarticle', function (Blueprint $table) {
            $table->dropColumn([
                'image_background', 'show_read_all', 'read_all_position', 'read_all_icon',
                'read_all_icon_position', 'read_all_style', 'read_all_url', 'read_all_link_target',
            ]);
        });
    }
};
