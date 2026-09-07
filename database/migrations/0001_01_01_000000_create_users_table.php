<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('sys_usergroup', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::create('sys_user', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('user_type', 20)->default('back');

            $table->foreignId('usergroup_id')->nullable()->constrained('sys_usergroup')->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();

            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('sys_action_group', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sys_action', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_group_id')->constrained('sys_action_group')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sys_usergroup_action', function (Blueprint $table) {
            $table->foreignId('usergroup_id')->constrained('sys_usergroup')->cascadeOnDelete();
            $table->foreignId('action_id')->constrained('sys_action')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['usergroup_id', 'action_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_usergroup_action');
        Schema::dropIfExists('sys_user');
        Schema::dropIfExists('sys_usergroup');
        Schema::dropIfExists('sys_action');
        Schema::dropIfExists('sys_action_group');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
