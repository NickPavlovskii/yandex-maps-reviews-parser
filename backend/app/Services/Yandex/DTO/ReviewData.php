<?php

namespace App\Services\Yandex\DTO;

readonly class ReviewData
{
    public function __construct(
        public ?string $id,
        public ?string $author,
        public ?int $rating,
        public ?string $date,
        public ?string $text,
        public ?string $language,
        public int $likes,
        public int $dislikes,
        public ?string $businessComment,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: isset($payload['id']) ? (string) $payload['id'] : null,
            author: isset($payload['author']) ? (string) $payload['author'] : null,
            rating: isset($payload['rating']) ? (int) $payload['rating'] : null,
            date: isset($payload['date']) ? (string) $payload['date'] : null,
            text: isset($payload['text']) ? (string) $payload['text'] : null,
            language: isset($payload['language']) ? (string) $payload['language'] : null,
            likes: (int) ($payload['likes'] ?? 0),
            dislikes: (int) ($payload['dislikes'] ?? 0),
            businessComment: isset($payload['businessComment'])
                ? (string) $payload['businessComment']
                : null,
        );
    }
}
