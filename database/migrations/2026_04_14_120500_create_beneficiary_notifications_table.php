<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_enrollment_id')->constrained('program_enrollments')->cascadeOnDelete();
            $table->string('notification_type', 50);
            $table->string('title', 200);
            $table->text('message')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamps();

            $table->index(['notification_type', 'status']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_notifications');
    }
};
