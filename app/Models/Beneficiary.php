<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beneficiary extends Model
{
    protected $fillable = [
        'beneficiary_no',
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'birthdate',
        'sex',
        'civil_status',
        'contact_number',
        'email',
        'address',
        'barangay',
        'municipality',
        'province',
        'field_office',
        'ofw_name',
        'relationship_to_ofw',
        'profile_photo_path',
        'category',
        'jobsite',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }
}
