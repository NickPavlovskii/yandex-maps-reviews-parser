<?php

namespace App\Services\Yandex\DTO;

readonly class OrganizationData
{
    public function __construct(
        public ?string $businessId,
        public ?string $name,
        public ?float $rating,
        public ?int $ratingsCount,
        public ?int $reviewsCount,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            businessId: isset($payload['businessId']) ? (string) $payload['businessId'] : null,
            name: isset($payload['name']) ? (string) $payload['name'] : null,
            rating: isset($payload['rating']) ? (float) $payload['rating'] : null,
            ratingsCount: isset($payload['ratingsCount']) ? (int) $payload['ratingsCount'] : null,
            reviewsCount: isset($payload['reviewsCount']) ? (int) $payload['reviewsCount'] : null,
        );
    }
}
