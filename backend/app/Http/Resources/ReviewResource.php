<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'yandex_review_id' => $this->yandex_review_id,
            'author' => $this->author,
            'rating' => $this->rating,
            'text' => $this->text,
            'business_reply' => $this->business_reply,
            'published_at' => $this->published_at?->toDateString(),
        ];
    }
}
