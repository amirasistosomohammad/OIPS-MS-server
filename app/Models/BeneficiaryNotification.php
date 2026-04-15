<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryNotification extends Model
{
    protected $fillable = [
        'program_enrollment_id',
        'notification_type',
        'title',
        'message',
        'due_date',
        'read_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'read_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ProgramEnrollment::class, 'program_enrollment_id');
    }
}
