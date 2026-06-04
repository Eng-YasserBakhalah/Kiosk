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
      Schema::create('terminal_devices', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('branch_id')
        ->constrained()
        ->cascadeOnDelete();

            $table->string('device_code')->unique();

            $table->string('serial_number')
        ->nullable();

            $table->string('location_label')
        ->nullable();

            $table->string('ip_address', 45)
        ->nullable();

            $table->string('app_version')
        ->nullable();

            $table->string('os_version')
        ->nullable();

            $table->enum('status', [
        'ACTIVE',
        'INACTIVE',
        'OFFLINE'
            ])->default('ACTIVE');

            $table->boolean('kiosk_mode_enabled')
        ->default(true);

            $table->timestamp('last_heartbeat_at')
        ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terminal_devices');
    }
};
