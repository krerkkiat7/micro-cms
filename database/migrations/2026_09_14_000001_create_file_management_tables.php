<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * โมดูลจัดการไฟล์ — แปลง schema เดิม (file_info/folder_info แบบ MSSQL-style) มาเป็น
     * convention ของโปรเจกต์ (snake_case, timestamps()+softDeletes(), status char(1) 'Y'/'N',
     * audit created_by/updated_by/deleted_by = sys_user.id ไม่มี FK). ไฟล์แต่ละไฟล์เป็นของ
     * ผู้ใช้คนเดียว (user_id) — หน้าจัดการไฟล์แสดงเฉพาะของเจ้าของเท่านั้น
     */
    public function up(): void
    {
        Schema::create('folder_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // เจ้าของโฟลเดอร์ (sys_user.id — เช็กในโค้ด ไม่มี FK)
            $table->string('name', 250);
            $table->char('status', 1)->default('Y'); // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });

        Schema::create('file_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // เจ้าของไฟล์ (sys_user.id — เช็กในโค้ด ไม่มี FK)
            $table->foreignId('folder_id')->nullable()->constrained('folder_info')->nullOnDelete();

            $table->string('name', 250);                // ชื่อไฟล์จริงที่ผู้ใช้อัพโหลด
            $table->string('hash_name', 250)->unique();  // ชื่อไฟล์ที่จัดเก็บจริง (ULID+นามสกุล) — ใช้เป็นค่าใน URL /admin/file/get/{hashname}
            $table->unsignedBigInteger('file_size')->default(0); // ขนาดไฟล์ (ไบต์)
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('path', 500); // path บน disk ต่อจาก root ของพื้นที่จัดเก็บ เช่น filemanager/2026/09/14/xxxx.jpg

            $table->char('status', 1)->default('Y'); // Y = ใช้งาน, N = ไม่ใช้งาน

            // ผู้กระทำ (sys_user.id — เช็ก/ผูกในโค้ด ไม่มี FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_info');
        Schema::dropIfExists('folder_info');
    }
};
