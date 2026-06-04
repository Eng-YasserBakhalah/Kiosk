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
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

    $table->uuid('user_id')
        ->nullable();

    $table->string('phone_masked', 30);

    $table->string('purpose', 80);

    $table->text('otp_hash');

    $table->integer('attempts')
        ->default(0);

    $table->enum('status', [
        'PENDING',
        'VERIFIED',
        'EXPIRED',
        'FAILED'
    ])->default('PENDING');

    $table->timestamp('expires_at');

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
    }
};
