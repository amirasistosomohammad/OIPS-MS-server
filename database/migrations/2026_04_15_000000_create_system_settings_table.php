<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('app_name')->default('OIPSMS');
            $table->string('logo_path')->nullable();
            $table->string('auth_background_path')->nullable();
            $table->string('backup_frequency')->default('off');
            $table->string('backup_run_at_time', 5)->default('02:00');
            $table->string('backup_timezone')->default('Asia/Manila');
            $table->timestamp('backup_last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
