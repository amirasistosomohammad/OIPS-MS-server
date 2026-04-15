<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_field_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('field_key', 80);
            $table->string('field_label', 150);
            $table->string('field_type', 40)->default('text');
            $table->string('field_scope', 20)->default('input');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->json('options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'field_key', 'field_scope'], 'pft_program_field_scope_unique');
            $table->index(['program_id', 'field_scope', 'display_order'], 'pft_program_scope_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_field_templates');
    }
};
