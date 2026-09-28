<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูล popup — popup ที่แสดงบนหน้าบ้าน (ทุกหน้า หรือเฉพาะเมนูที่ระบุ) แบบ modal หรือแบบลอย (floating)
     * 1 popup มีข้อมูลได้หลายรายการ (part) ที่แสดงเป็นสไลด์ ดู docs/PRD-popup.md
     *
     * - popup_item_info        ข้อมูลร่วม + ตั้งค่าการแสดงผล/สไลด์ + ช่วงเผยแพร่
     * - popup_item_menu        เมนูหน้าบ้านที่แสดง popup (ใช้เมื่อ menu_mode = selected)
     * - popup_item_part        ข้อมูลแต่ละรายการ (รูปภาพ + ข้อความ / รูปภาพ / ข้อความ) เรียงลำดับได้
     * - popup_item_part_detail ข้อความของ part แยกตามภาษา (PK = id+lang)
     *
     * เทียบกับตารางเดิมของระบบเก่า: ตัดคอลัมน์ที่ไม่ได้ใช้ออก (CustomCss/Width/Height/BackgroundColor/Padding/
     * TemplateType/TemplateSetting และวิดีโอของ part) ส่วน DisplayType แบบ U (หน้าที่ไม่ถูกเลือก) ไม่ได้ใช้แล้ว
     */
    public function up(): void
    {
        Schema::create('popup_item_info', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250); // ชื่อ popup (ใช้ในหลังบ้าน ไม่แสดงหน้าบ้าน)
            $table->string('display_type', 10)->default('modal'); // modal = มีพื้นหลังทึบ, floating = ลอยกลางจอ ไม่มีพื้นหลัง
            $table->char('show_dismiss_today', 1)->default('Y'); // Y = แสดงปุ่ม/ลิงก์ "ไม่แสดงวันนี้อีก"
            $table->char('show_arrows', 1)->default('Y'); // แสดงลูกศรเลื่อนสไลด์
            $table->char('show_dots', 1)->default('Y'); // แสดงจุดด้านล่าง
            $table->char('autoplay', 1)->default('Y'); // สไลด์อัตโนมัติ
            $table->unsignedInteger('slide_interval')->default(5); // เวลาที่ค้างแต่ละสไลด์ (วินาที)
            $table->unsignedInteger('slide_speed')->default(3000); // ความเร็วการสไลด์ (มิลลิวินาที)
            $table->string('menu_mode', 10)->default('all'); // all = ทุกหน้า, selected = เมนูที่ระบุ, none = ไม่กำหนด (ไม่แสดง)
            $table->dateTime('publish_date')->nullable(); // วันที่เผยแพร่
            $table->dateTime('publish_down')->nullable(); // วันที่ปิดการเผยแพร่
            $table->unsignedInteger('sort_order')->default(0); // ลำดับ
            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด
            $table->char('is_temp', 1)->default('N'); // Y = ข้อมูลตัวอย่าง/ตั้งต้น

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('publish_date');
            $table->index('sort_order');
        });

        Schema::create('popup_item_menu', function (Blueprint $table) {
            $table->foreignId('popup_item_info_id')->constrained('popup_item_info', 'id', 'popup_item_menu_popup_fk')->cascadeOnDelete();
            $table->foreignId('front_menu_info_id')->constrained('front_menu_info', 'id', 'popup_item_menu_menu_fk')->cascadeOnDelete();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->primary(['popup_item_info_id', 'front_menu_info_id']);
        });

        Schema::create('popup_item_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_item_info_id')->constrained('popup_item_info', 'id', 'popup_item_part_popup_fk')->cascadeOnDelete();
            $table->string('part_type', 20)->default('image_text'); // image_text = รูปภาพ + ข้อความ, image = รูปภาพ, text = ข้อความ
            $table->foreignId('image_id')->nullable()->constrained('file_info', 'id', 'popup_item_part_image_fk')->nullOnDelete(); // รูปภาพ (ใช้ร่วมทุกภาษา)
            $table->string('image_size', 10)->default('full'); // full = เต็มความกว้าง, large, medium, small
            $table->string('url', 500)->nullable(); // ลิงก์ปลายทาง
            $table->string('link_target', 10)->default('_blank'); // _self, _blank
            $table->unsignedInteger('sort_order')->default(0);
            $table->char('status', 1)->default('Y'); // Y = แสดง, N = ซ่อน (ไอคอนลูกตา — ต่างจาก soft delete)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['popup_item_info_id', 'sort_order']);
        });

        Schema::create('popup_item_part_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = popup_item_part.id
            $table->char('lang', 2);

            $table->text('detail')->nullable(); // ข้อความ (rich text)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id', 'popup_item_part_detail_part_fk')->references('id')->on('popup_item_part')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popup_item_part_detail');
        Schema::dropIfExists('popup_item_part');
        Schema::dropIfExists('popup_item_menu');
        Schema::dropIfExists('popup_item_info');
    }
};
