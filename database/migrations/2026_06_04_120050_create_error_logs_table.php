<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_id', 120)->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('digital_service_users')->nullOnDelete();
            $table->foreignUuid('terminal_device_id')->nullable()->constrained('terminal_devices')->nullOnDelete();
            $table->string('service_code', 80)->nullable();
            $table->string('error_type', 80)->nullable();
            $table->string('error_level', 30)->default('ERROR');
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->string('source', 80)->nullable();
            $table->text('stack_trace')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
