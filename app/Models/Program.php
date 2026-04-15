<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = [
        'program_code',
        'program_name',
        'program_type',
        'update_mode',
        'update_interval_months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'update_interval_months' => 'integer',
        ];
    }

    public function statusOptions(): HasMany
    {
        return $this->hasMany(ProgramStatusOption::class)->orderBy('display_order');
    }

    public function fieldTemplates(): HasMany
    {
        return $this->hasMany(ProgramFieldTemplate::class)->orderBy('field_scope')->orderBy('display_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }
}
