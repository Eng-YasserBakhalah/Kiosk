<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_opening_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_id', 120)->unique();
            $table->foreignUuid('user_id')->nullable()->constrained('digital_service_users')->nullOnDelete();
            $table->foreignUuid('terminal_device_id')->nullable()->constrained('terminal_devices')->nullOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number', 120)->unique();
            $table->string('bank_reference', 150)->nullable();
            $table->string('account_type', 80);
            $table->string('currency', 10)->default('SAR');
            $table->string('full_name', 180);
            $table->string('phone_masked', 30);
            $table->string('national_id_masked', 40);
            $table->text('address')->nullable();
            $table->string('income_source', 120)->nullable();
            $table->string('status', 40)->default('SUBMITTED');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_opening_requests');
    }
};
