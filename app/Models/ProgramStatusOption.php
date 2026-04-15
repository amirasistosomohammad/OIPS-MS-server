<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStatusOption extends Model
{
    protected $fillable = [
        'program_id',
        'status_code',
        'status_label',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function updateEntries(): HasMany
    {
        return $this->hasMany(ProgramUpdateEntry::class, 'status_option_id');
    }
}
