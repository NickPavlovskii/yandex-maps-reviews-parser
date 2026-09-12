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
            'avg_rating' => $this->avg_rating !== null ? (float) $this->avg_rating : null,
            'ratings_count' => $this->ratings_count,
            'reviews_count' => $this->reviews_count,
            'parse_status' => $this->parse_status?->value,
            'last_parsed_at' => $this->last_parsed_at?->toIso8601String(),
        ];
    }
}
