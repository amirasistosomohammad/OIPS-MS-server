<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table): void {
            $table->id();
            $table->string('beneficiary_no', 50)->unique();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix', 20)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('civil_status', 30)->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('barangay', 120)->nullable();
            $table->string('municipality', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->string('field_office', 120)->nullable();
            $table->string('ofw_name')->nullable();
            $table->string('relationship_to_ofw', 120)->nullable();
            $table->string('category', 120)->nullable();
            $table->string('jobsite', 150)->nullable();
            $table->string('position', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index(['field_office', 'province', 'municipality']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
