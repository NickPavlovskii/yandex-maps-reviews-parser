<?php

namespace App\Models;

use App\Enums\ParseStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'url',
    'yandex_business_id',
    'name',
    'avg_rating',
    'ratings_count',
    'reviews_count',
    'parse_status',
    'last_parsed_at',
])]
class Organization extends Model
{
 
    use HasFactory;

    protected function casts(): array
    {
        return [
            'avg_rating' => 'decimal:2',
            'ratings_count' => 'integer',
            'reviews_count' => 'integer',
            'parse_status' => ParseStatus::class,
            'last_parsed_at' => 'datetime',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function parseRuns(): HasMany
    {
        return $this->hasMany(ParseRun::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ReviewSnapshot::class);
    }
}
