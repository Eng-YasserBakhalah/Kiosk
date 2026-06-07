<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_service_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('digital_services')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->decimal('daily_limit', 18, 2)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_service_settings');
    }
};
