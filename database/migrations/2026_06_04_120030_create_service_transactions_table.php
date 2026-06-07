<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_id', 120)->unique();
            $table->string('idempotency_key', 150)->nullable()->unique();
            $table->foreignUuid('user_id')->nullable()->constrained('digital_service_users')->nullOnDelete();
            $table->foreignUuid('terminal_device_id')->nullable()->constrained('terminal_devices')->nullOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('digital_services')->nullOnDelete();
            $table->string('bank_reference', 150)->nullable();
            $table->string('transaction_type', 80);
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('status', 40)->default('INITIATED');
            $table->string('response_code', 80)->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['terminal_device_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_transactions');
    }
};
