<?php

namespace App\Models;

use App\Enums\ParseRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'status',
    'reviews_found',
    'error_message',
    'started_at',
    'finished_at',
])]
class ParseRun extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ParseRunStatus::class,
            'reviews_found' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
