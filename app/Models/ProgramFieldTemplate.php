<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramFieldTemplate extends Model
{
    protected $fillable = [
        'program_id',
        'field_key',
        'field_label',
        'field_type',
        'field_scope',
        'is_required',
        'display_order',
        'options',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'options' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
