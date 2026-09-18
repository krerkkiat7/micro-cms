<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูล Intropage (หน้าคั่นก่อนเข้าเว็บ/splash) — เก็บได้หลายชุด (intropage_item_info +
     * intropage_item_detail แยกภาษา เหมือน banner_item_*) แต่ "แสดงจริง" ได้ทีละ 1 ชุดเท่านั้น — กติกาเลือกว่า
     * จะแสดงชุดไหน (ถ้าช่วงเวลาเผยแพร่ทับกัน) คำนวณที่หน้าบ้านในรอบถัดไป ฝั่งนี้เก็บแค่ status/publish_date/
     * publish_down ตามปกติ ไม่มีกลไกสลับ/exclusive switch ในหลังบ้าน (ดู docs/PRD-intropage.md)
     *
     * แต่ละ intropage มีพื้นหลัง (สี/รูป/วิดีโอไฟล์/วิดีโอ URL/YouTube) + ข้อความต้อนรับต่อภาษา + ชุดปุ่มด้านล่าง
     * ที่เรียงลำดับ/เพิ่ม/ลบได้ (intropage_item_button) — ปุ่มประเภท home มีอยู่เสมอ 1 ปุ่ม ลบไม่ได้ (บังคับที่
     * FormRequest) ปุ่มอื่นเป็น button_type = other เพิ่ม/ลบ/เรียงลำดับได้อิสระ
     */
    public function up(): void
    {
        Schema::create('intropage_item_info', function (Blueprint $table) {
            $table->id();

            // พื้นหลัง
            $table->string('background_color', 20)->nullable();
            $table->foreignId('background_image_id')->nullable()->constrained('file_info')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable(); // repeat, no-repeat, repeat-x, repeat-y
            $table->string('background_size', 50)->nullable(); // auto, cover, contain
            $table->string('background_attachment', 50)->nullable(); // scroll, fixed
            $table->string('background_position', 50)->nullable(); // เช่น center, top left, bottom right

            // สื่อหลักของหน้า Intropage
            $table->string('display_type', 10)->nullable(); // image, vdo, vdourl, youtubeurl
            $table->string('display_size', 20)->nullable(); // screen_100/75/50/25, container_100/75/50/25
            $table->foreignId('image_file_id')->nullable()->constrained('file_info')->nullOnDelete(); // display_type = image
            $table->foreignId('vdo_file_id')->nullable()->constrained('file_info')->nullOnDelete(); // display_type = vdo
            $table->string('vdo_url', 500)->nullable(); // display_type = vdourl หรือ youtubeurl

            $table->char('show_button', 1)->default('Y'); // แสดง/ซ่อนโซนปุ่มทั้งหมด

            $table->dateTime('publish_date')->nullable(); // วันที่ประกาศ
            $table->dateTime('publish_down')->nullable(); // วันที่ปิดประกาศ

            $table->char('status', 1)->default('Y'); // Y = เปิดใช้งาน, N = ปิด
            $table->char('is_temp', 1)->default('N'); // Y = ข้อมูลตัวอย่าง/ตั้งต้น (ลบทิ้งภายหลังได้)

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('publish_date');
        });

        Schema::create('intropage_item_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id'); // = intropage_item_info.id
            $table->char('lang', 2);

            $table->string('title', 500)->nullable(); // ชื่อ — required เฉพาะภาษาหลัก
            $table->text('detail')->nullable(); // ข้อความต้อนรับ

            $table->char('status', 1)->default('Y');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary(['id', 'lang']);
            $table->foreign('id')->references('id')->on('intropage_item_info')->cascadeOnDelete();
        });

        Schema::create('intropage_item_button', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intropage_item_info_id')->constrained('intropage_item_info')->cascadeOnDelete();

            $table->string('button_type', 10); // home (เสมอ 1 ปุ่ม ลบไม่ได้) หรือ other (เพิ่ม/ลบได้อิสระ)
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('button_display_type', 10)->default('text'); // text หรือ image

            $table->string('background_color', 10)->nullable(); // เฉพาะ button_display_type = text
            $table->string('text_color', 10)->nullable(); // เฉพาะ button_display_type = text
            $table->foreignId('button_image_id')->nullable()->constrained('file_info')->nullOnDelete(); // เฉพาะ button_display_type = image

            $table->string('url', 500)->nullable(); // เฉพาะ button_type = other
            $table->string('link_target', 20)->nullable(); // _self, _blank — เฉพาะ button_type = other

            $table->json('texts')->nullable(); // ข้อความปุ่มแยกตามภาษา เช่น {"th": "...", "en": "..."}

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['intropage_item_info_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intropage_item_button');
        Schema::dropIfExists('intropage_item_detail');
        Schema::dropIfExists('intropage_item_info');
    }
};
