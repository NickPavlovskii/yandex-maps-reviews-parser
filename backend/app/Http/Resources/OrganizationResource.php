<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 */
class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'yandex_business_id' => $this->yandex_business_id,
            'name' => $this->name,
            'avg_rating' => $this->resolvedAvgRating(),
            'ratings_count' => $this->resolvedRatingsCount(),
            'reviews_count' => $this->reviews_count,
            'parse_status' => $this->parse_status?->value,
            'last_parsed_at' => $this->last_parsed_at?->toIso8601String(),
            'rating_breakdown' => collect($this->ratingBreakdown())
                ->map(fn (int $count, int $rating): array => [
                    'rating' => (int) $rating,
                    'count' => $count,
                ])
                ->values()
                ->all(),
            'last_parse_duration_seconds' => $this->lastParseDurationSeconds(),
            'aspects' => $this->aspects ?? [],
            'rating_history' => $this->ratingHistory(),
        ];
    }
}
