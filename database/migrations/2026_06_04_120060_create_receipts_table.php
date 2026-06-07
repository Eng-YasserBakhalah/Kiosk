<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->nullable()->constrained('service_transactions')->nullOnDelete();
            $table->string('receipt_number', 120)->unique();
            $table->string('bank_reference', 150)->nullable();
            $table->string('receipt_type', 50)->default('DIGITAL');
            $table->json('masked_payload');
            $table->text('qr_payload')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
