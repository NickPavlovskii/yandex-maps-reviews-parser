<?php

namespace App\Services\Yandex;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\Review;
use App\Models\ReviewSnapshot;
use App\Services\Yandex\DTO\ParsedOrganization;
use App\Services\Yandex\DTO\ReviewData;
use Illuminate\Support\Facades\DB;

class PersistParsedOrganization
{
    public function persist(Organization $organization, ParsedOrganization $parsed): Organization
    {
        return DB::transaction(function () use ($organization, $parsed): Organization {
            $reviews = array_values(array_filter(
                $parsed->reviews,
                static fn (ReviewData $review): bool => filled($review->yandexReviewId),
            ));

            $reviewRatings = array_values(array_filter(
                array_map(static fn (ReviewData $review): ?int => $review->rating, $reviews),
                static fn (?int $rating): bool => $rating !== null,
            ));

            $avgRating = $parsed->organization->rating;
            if ($avgRating === null && $reviewRatings !== []) {
                $avgRating = round(array_sum($reviewRatings) / count($reviewRatings), 2);
            }

            $ratingsCount = $parsed->organization->ratingsCount;
            if (($ratingsCount === null || $ratingsCount === 0) && $reviewRatings !== []) {
                $ratingsCount = count($reviewRatings);
            }

            $organization->fill([
                'yandex_business_id' => $parsed->organization->businessId ?? $organization->yandex_business_id,
                'name' => $parsed->organization->name ?? $organization->name,
                'avg_rating' => $avgRating,
                'ratings_count' => $ratingsCount,
                'reviews_count' => $parsed->organization->reviewsCount ?? count($reviews),
                'parse_status' => ParseStatus::Success,
                'last_parsed_at' => now(),
            ]);
            $organization->save();

            foreach ($reviews as $review) {
                Review::query()->updateOrCreate(
                    ['yandex_review_id' => $review->yandexReviewId],
                    [
                        'organization_id' => $organization->id,
                        'author' => $review->author,
                        'rating' => $review->rating,
                        'text' => $review->text,
                        'business_reply' => $review->businessReply,
                        'published_at' => $review->publishedAt,
                    ],
                );
            }

            ReviewSnapshot::query()->create([
                'organization_id' => $organization->id,
                'avg_rating' => $organization->avg_rating,
                'ratings_count' => $organization->ratings_count,
                'reviews_count' => $organization->reviews_count,
            ]);

            return $organization->refresh();
        });
    }
}
