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
        Schema::create('digital_service_users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('bank_customer_ref')
                ->unique();

            $table->string('username')
                ->unique()
                ->nullable();

            $table->string('phone_masked', 30)
                ->nullable();

            $table->text('password_hash')
                ->nullable();

            $table->boolean('biometric_enabled')
                ->default(false);

            $table->enum('status', [
                'PENDING',
                'ACTIVE',
                'LOCKED',
                'DISABLED',
            ])->default('PENDING');

            $table->integer('failed_login_attempts')
                ->default(0);

            $table->timestamp('locked_until')
                ->nullable();

            $table->timestamp('last_login_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_service_users');
    }
};
