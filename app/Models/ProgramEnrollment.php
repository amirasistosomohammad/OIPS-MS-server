<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramEnrollment extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'program_id',
        'batch',
        'date_enrolled',
        'enrollment_status',
        'last_update_at',
        'next_update_due_at',
        'created_by_name',
        'notes',
        'input_payload',
    ];

    protected function casts(): array
    {
        return [
            'date_enrolled' => 'date',
            'last_update_at' => 'date',
            'next_update_due_at' => 'date',
            'input_payload' => 'array',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function updateEntries(): HasMany
    {
        return $this->hasMany(ProgramUpdateEntry::class)->orderByDesc('update_date');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(BeneficiaryNotification::class);
    }
}
