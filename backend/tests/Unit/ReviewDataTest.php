<?php

namespace Tests\Unit;

use App\Services\Yandex\DTO\ReviewData;
use PHPUnit\Framework\TestCase;

class ReviewDataTest extends TestCase
{
    public function test_it_maps_the_node_contract_keys(): void
    {
        $review = ReviewData::fromArray([
            'yandexReviewId' => 'review-1',
            'author' => 'Иван',
            'rating' => 5,
            'text' => 'Отличное место',
            'businessReply' => 'Спасибо',
            'publishedAt' => '2026-08-20',
            'language' => 'ru',
            'likes' => 3,
            'dislikes' => 1,
        ]);

        $this->assertSame('review-1', $review->yandexReviewId);
        $this->assertSame('Иван', $review->author);
        $this->assertSame(5, $review->rating);
        $this->assertSame('Отличное место', $review->text);
        $this->assertSame('Спасибо', $review->businessReply);
        $this->assertSame('2026-08-20', $review->publishedAt);
        $this->assertSame('ru', $review->language);
        $this->assertSame(3, $review->likes);
        $this->assertSame(1, $review->dislikes);
    }

    public function test_it_does_not_map_legacy_review_keys(): void
    {
        $review = ReviewData::fromArray([
            'id' => 'review-2',
            'date' => '2026-08-19',
            'businessComment' => 'Благодарим',
            'author' => 'Анна',
        ]);

        $this->assertNull($review->yandexReviewId);
        $this->assertNull($review->publishedAt);
        $this->assertNull($review->businessReply);
        $this->assertSame('Анна', $review->author);
    }
}
