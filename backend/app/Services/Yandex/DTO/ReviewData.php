<?php

namespace App\Services\Yandex\DTO;

readonly class ReviewData
{
    public function __construct(
        public ?string $yandexReviewId,
        public ?string $author,
        public ?int $rating,
        public ?string $publishedAt,
        public ?string $text,
        public ?string $language,
        public int $likes,
        public int $dislikes,
        public ?string $businessReply,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            yandexReviewId: isset($payload['yandexReviewId']) ? (string) $payload['yandexReviewId'] : null,
            author: isset($payload['author']) ? (string) $payload['author'] : null,
            rating: isset($payload['rating']) ? (int) $payload['rating'] : null,
            publishedAt: isset($payload['publishedAt']) ? (string) $payload['publishedAt'] : null,
            text: isset($payload['text']) ? (string) $payload['text'] : null,
            language: isset($payload['language']) ? (string) $payload['language'] : null,
            likes: (int) ($payload['likes'] ?? 0),
            dislikes: (int) ($payload['dislikes'] ?? 0),
            businessReply: isset($payload['businessReply']) ? (string) $payload['businessReply'] : null,
        );
    }
}
