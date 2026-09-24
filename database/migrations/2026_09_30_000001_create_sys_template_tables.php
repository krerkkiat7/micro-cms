<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลจัดการ Template หน้าบ้าน (system.template) — ดู docs/PRD-system-template.md
     * - `sys_template` = ข้อมูลทั่วไป + Custom CSS/JS + หน้า Loading (เปิดใช้งานได้ครั้งละ 1 รายการ — บังคับในโค้ด)
     * - ตั้งค่าแต่ละโซนแยกตาราง 1:1 (PK = `sys_template_id`, สร้างพร้อม template เสมอ) — header / body / footer / aside
     *   เก็บเป็นคอลัมน์แบน ไม่ใช้ JSON; ค่า default ของคอลัมน์ต้องตรงกับ App\Support\Template\TemplateZone
     * ข้อมูลไซต์ (โลโก้ / ชื่อไซต์ / ข้อมูลติดต่อ / social / ภาษา / ลิขสิทธิ์) อ่านจาก sys_setting ไม่เก็บซ้ำในตารางนี้
     */
    public function up(): void
    {
        Schema::create('sys_template', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250)->comment('ชื่อ template');
            $table->string('preset', 30)->nullable()->comment('แม่แบบที่เลือกตอนสร้าง (App\Support\Template\TemplatePreset)');

            // Custom CSS / JS (แท็บ "Custom CSS/JS")
            $table->char('custom_css_status', 1)->default('N')->comment('ใช้งาน Custom CSS');
            $table->mediumText('custom_css')->nullable()->comment('Custom CSS');
            $table->char('custom_js_status', 1)->default('N')->comment('ใช้งาน Custom JS');
            $table->text('custom_js')->nullable()->comment('Custom JS');

            // หน้า Loading (แท็บ "หน้า Loading")
            $table->char('loading_status', 1)->default('N')->comment('ใช้งานหน้า Loading');
            $table->string('loading_type', 20)->default('spinner')->comment('spinner = ตัวหมุนของระบบ / image = รูปจากไฟล์');
            $table->string('loading_spinner', 20)->default('ring')->comment('รูปแบบตัวหมุน: ring / dots / bar');
            $table->string('loading_color', 20)->default('#2563EB')->comment('สีตัวหมุน');
            $table->string('loading_background_color', 20)->default('#FFFFFF')->comment('สีพื้นหลังหน้า Loading');
            $table->foreignId('loading_image_id')->nullable()->constrained('file_info', 'id', 'sys_template_loading_image_foreign')->nullOnDelete()->comment('รูป Loading (loading_type = image)');

            $table->timestamp('layout_updated_at')->nullable()->comment('วันที่บันทึกโครงสร้างล่าสุด');
            $table->unsignedBigInteger('layout_updated_by')->nullable()->comment('ผู้บันทึกโครงสร้างล่าสุด (sys_user.id)');

            $table->char('status', 1)->default('N')->comment('Y = template ที่ใช้งานอยู่ (ได้แถวเดียว)');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sys_template_header', function (Blueprint $table) {
            $table->unsignedBigInteger('sys_template_id')->primary();
            $table->char('status', 1)->default('Y')->comment('แสดง header');
            $table->string('layout_type', 20)->default('topbar_main')->comment('topbar_main / main_menubar / main_only');
            $table->char('sticky', 1)->default('N')->comment('แสดง header เสมอเมื่อเลื่อนลง');

            // โลโก้
            $table->char('logo_status', 1)->default('Y');
            $table->string('logo_align', 10)->default('left')->comment('left / center / right');
            $table->string('logo_display', 20)->default('image_name')->comment('image / image_name / name');
            $table->string('logo_action', 10)->default('home')->comment('none = แสดงเท่านั้น / home = ลิงก์ไปหน้าแรก');

            // เมนู
            $table->string('menu_align', 10)->default('right')->comment('left / center / right');
            $table->string('menu_style', 20)->default('underline')->comment('plain / underline / pill / divider');
            $table->string('menu_text_color', 20)->default('#1F2937');
            $table->string('menu_active_color', 20)->default('#2563EB');

            // ภาษา
            $table->char('lang_status', 1)->default('Y');
            $table->string('lang_display', 20)->default('flag_code')->comment('code / flag / flag_code');
            $table->string('lang_select', 20)->default('dropdown')->comment('all = แสดงทั้งหมด / dropdown = เป็นตัวเลือก');

            $table->char('social_status', 1)->default('Y');
            $table->char('search_status', 1)->default('Y');
            $table->char('fontsize_status', 1)->default('Y');
            $table->string('fontsize_display', 10)->default('icon')->comment('icon / text');
            $table->char('contrast_status', 1)->default('Y');
            $table->string('contrast_display', 10)->default('icon')->comment('icon / text');

            // แถบหลัก
            $table->string('main_width', 20)->default('container')->comment('full / container');
            $table->string('main_text_color', 20)->default('#1F2937');
            $table->string('background_color', 20)->nullable()->default('#FFFFFF');
            $table->foreignId('background_image_id')->nullable()->constrained('file_info', 'id', 'sys_template_header_bg_foreign')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable();
            $table->string('background_size', 50)->nullable();
            $table->string('background_attachment', 50)->nullable();
            $table->string('background_position', 50)->nullable();

            // แถบบน (layout_type = topbar_main)
            $table->string('topbar_width', 20)->default('full');
            $table->string('topbar_background_color', 20)->default('#1E3A8A');
            $table->string('topbar_text_color', 20)->default('#FFFFFF');

            // แถวเมนู (layout_type = main_menubar)
            $table->string('menubar_width', 20)->default('full');
            $table->string('menubar_background_color', 20)->default('#2563EB');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sys_template_id', 'sys_template_header_template_foreign')->references('id')->on('sys_template')->cascadeOnDelete();
        });

        Schema::create('sys_template_body', function (Blueprint $table) {
            $table->unsignedBigInteger('sys_template_id')->primary();
            $table->string('background_color', 20)->nullable()->default('#F9FAFB');
            $table->foreignId('background_image_id')->nullable()->constrained('file_info', 'id', 'sys_template_body_bg_foreign')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable();
            $table->string('background_size', 50)->nullable();
            $table->string('background_attachment', 50)->nullable();
            $table->string('background_position', 50)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sys_template_id', 'sys_template_body_template_foreign')->references('id')->on('sys_template')->cascadeOnDelete();
        });

        Schema::create('sys_template_footer', function (Blueprint $table) {
            $table->unsignedBigInteger('sys_template_id')->primary();
            $table->char('status', 1)->default('Y')->comment('แสดง footer');
            $table->string('layout_type', 30)->default('site_contact_menu')->comment('site_contact_menu / site_contact_center / site_contact_block');
            $table->string('width', 20)->default('full')->comment('full / container');
            $table->string('background_color', 20)->nullable()->default('#1F2937');
            $table->foreignId('background_image_id')->nullable()->constrained('file_info', 'id', 'sys_template_footer_bg_foreign')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable();
            $table->string('background_size', 50)->nullable();
            $table->string('background_attachment', 50)->nullable();
            $table->string('background_position', 50)->nullable();

            // ตัวอักษรหัวข้อ / เนื้อหา
            $table->string('heading_color', 20)->default('#FFFFFF');
            $table->unsignedSmallInteger('heading_font_size')->default(18);
            $table->string('heading_font_family', 50)->default('Sarabun');
            $table->char('heading_bold', 1)->default('Y');
            $table->string('text_color', 20)->default('#D1D5DB');
            $table->unsignedSmallInteger('text_font_size')->default(14);
            $table->string('text_font_family', 50)->default('Sarabun');
            $table->char('text_bold', 1)->default('N');

            // ข้อมูลที่แสดง (ค่าจาก sys_setting กลุ่ม contact / social + เมนูหน้าบ้าน)
            $table->char('show_address', 1)->default('Y');
            $table->char('show_phone', 1)->default('Y');
            $table->char('show_fax', 1)->default('Y');
            $table->char('show_mobile', 1)->default('Y');
            $table->char('show_email', 1)->default('Y');
            $table->char('show_social', 1)->default('Y');
            $table->char('show_menu', 1)->default('Y')->comment('ใช้กับ layout_type = site_contact_menu');

            // แถบลิขสิทธิ์
            $table->char('copyright_status', 1)->default('Y');
            $table->string('copyright_width', 20)->default('full')->comment('full / container');
            $table->string('copyright_background_color', 20)->default('#111827');
            $table->string('copyright_text_color', 20)->default('#9CA3AF');
            $table->unsignedSmallInteger('copyright_font_size')->default(13);
            $table->string('copyright_font_family', 50)->default('Sarabun');
            $table->string('copyright_align', 10)->default('center')->comment('left / center / right');
            $table->char('copyright_show_owner', 1)->default('Y')->comment('แสดงชื่อเจ้าของไซต์ (site.copyright_owner)');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sys_template_id', 'sys_template_footer_template_foreign')->references('id')->on('sys_template')->cascadeOnDelete();
        });

        Schema::create('sys_template_aside', function (Blueprint $table) {
            $table->unsignedBigInteger('sys_template_id')->primary();
            $table->char('status', 1)->default('Y')->comment('ใช้งานเมนูข้าง (aside)');
            $table->string('toggle_position', 10)->default('right')->comment('ตำแหน่งไอคอนที่กดเปิด: left / right');
            $table->string('display_type', 20)->default('drawer')->comment('fullscreen = เต็มจอ / drawer = แถบข้าง');
            $table->string('background_color', 20)->nullable()->default('#FFFFFF');
            $table->foreignId('background_image_id')->nullable()->constrained('file_info', 'id', 'sys_template_aside_bg_foreign')->nullOnDelete();
            $table->string('background_repeat', 50)->nullable();
            $table->string('background_size', 50)->nullable();
            $table->string('background_attachment', 50)->nullable();
            $table->string('background_position', 50)->nullable();
            $table->string('text_color', 20)->default('#1F2937');
            $table->string('menu_style', 20)->default('accordion')->comment('list / accordion / drilldown / large');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sys_template_id', 'sys_template_aside_template_foreign')->references('id')->on('sys_template')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_template_aside');
        Schema::dropIfExists('sys_template_footer');
        Schema::dropIfExists('sys_template_body');
        Schema::dropIfExists('sys_template_header');
        Schema::dropIfExists('sys_template');
    }
};
