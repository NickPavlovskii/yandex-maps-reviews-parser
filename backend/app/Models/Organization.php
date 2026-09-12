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

    public function resolvedAvgRating(): ?float
    {
        if ($this->avg_rating !== null) {
            return (float) $this->avg_rating;
        }
        $average = $this->reviews()->whereNotNull('rating')->avg('rating');
        return $average === null ? null : round((float) $average, 2);
    }

    public function resolvedRatingsCount(): ?int
    {
        if ($this->ratings_count) {
            return $this->ratings_count;
        }

        $count = $this->reviews()->whereNotNull('rating')->count();
        return $count > 0 ? $count : $this->ratings_count;
    }

    public function ratingBreakdown(): array
    {
        $counts = $this->reviews()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return [
            5 => (int) ($counts[5] ?? 0),
            4 => (int) ($counts[4] ?? 0),
            3 => (int) ($counts[3] ?? 0),
            2 => (int) ($counts[2] ?? 0),
            1 => (int) ($counts[1] ?? 0),
        ];
    }

    public function lastParseDurationSeconds(): ?int
    {
        $run = $this->parseRuns()
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->first();

        if ($run?->started_at === null || $run->finished_at === null) {
            return null;
        }

        return (int) $run->started_at->diffInSeconds($run->finished_at);
    }
}
