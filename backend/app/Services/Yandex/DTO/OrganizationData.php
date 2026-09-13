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
        public array $aspects = [],
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
            aspects: self::aspectsFrom($payload['aspects'] ?? []),
        );
    }

    /**
     * @return list<array{text: string, count: int, positive: int, negative: int}>
     */
    private static function aspectsFrom(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $aspects = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $text = trim((string) ($item['text'] ?? $item['name'] ?? ''));

            if ($text === '') {
                continue;
            }

            $aspects[] = [
                'text' => $text,
                'count' => (int) ($item['count'] ?? 0),
                'positive' => (int) ($item['positive'] ?? 0),
                'negative' => (int) ($item['negative'] ?? 0),
            ];
        }

        return $aspects;
    }
}
