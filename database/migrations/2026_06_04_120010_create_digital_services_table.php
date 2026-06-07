<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('service_code', 80)->unique();
            $table->string('service_name', 200);
            $table->string('category', 80);
            $table->string('api_endpoint_key', 120)->nullable();
            $table->boolean('requires_otp')->default(false);
            $table->boolean('requires_password')->default(false);
            $table->boolean('requires_biometric')->default(false);
            $table->boolean('enabled')->default(true);
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_services');
    }
};
