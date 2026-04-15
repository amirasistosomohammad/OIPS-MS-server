<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramUpdateEntry extends Model
{
    protected $fillable = [
        'program_enrollment_id',
        'status_option_id',
        'update_date',
        'update_payload',
        'amount_received',
        'remarks',
        'updated_by_name',
    ];

    protected function casts(): array
    {
        return [
            'update_date' => 'date',
            'update_payload' => 'array',
            'amount_received' => 'decimal:2',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ProgramEnrollment::class, 'program_enrollment_id');
    }

    public function statusOption(): BelongsTo
    {
        return $this->belongsTo(ProgramStatusOption::class, 'status_option_id');
    }
}
