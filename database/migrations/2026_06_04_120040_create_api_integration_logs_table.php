<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_integration_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_id', 120);
            $table->foreignUuid('service_id')->nullable()->constrained('digital_services')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('digital_service_users')->nullOnDelete();
            $table->foreignUuid('terminal_device_id')->nullable()->constrained('terminal_devices')->nullOnDelete();
            $table->string('external_api_name', 120);
            $table->string('endpoint_key', 120);
            $table->string('http_method', 10)->nullable();
            $table->integer('response_status')->nullable();
            $table->string('bank_response_code', 80)->nullable();
            $table->integer('duration_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->json('masked_request')->nullable();
            $table->json('masked_response')->nullable();
            $table->timestamps();

            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_integration_logs');
    }
};
