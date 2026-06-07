<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('digital_service_users')->cascadeOnDelete();
            $table->foreignUuid('terminal_device_id')->constrained('terminal_devices')->cascadeOnDelete();
            $table->text('access_token_hash');
            $table->text('refresh_token_hash')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('login_method', 30);
            $table->dateTime('login_at')->useCurrent();
            $table->dateTime('expires_at');
            $table->dateTime('logout_at')->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['terminal_device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
