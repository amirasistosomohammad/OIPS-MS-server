<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_update_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_enrollment_id')->constrained('program_enrollments')->cascadeOnDelete();
            $table->foreignId('status_option_id')->nullable()->constrained('program_status_options')->nullOnDelete();
            $table->date('update_date');
            $table->json('update_payload')->nullable();
            $table->decimal('amount_received', 12, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('updated_by_name')->nullable();
            $table->timestamps();

            $table->index(['program_enrollment_id', 'update_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_update_entries');
    }
};
