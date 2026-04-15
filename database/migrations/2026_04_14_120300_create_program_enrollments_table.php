<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('batch', 80)->nullable();
            $table->date('date_enrolled')->nullable();
            $table->string('enrollment_status', 40)->default('active');
            $table->date('last_update_at')->nullable();
            $table->date('next_update_due_at')->nullable();
            $table->string('created_by_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['program_id', 'enrollment_status']);
            $table->index(['next_update_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_enrollments');
    }
};
