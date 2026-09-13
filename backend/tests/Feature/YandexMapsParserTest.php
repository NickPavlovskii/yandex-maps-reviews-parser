<?php

namespace Tests\Feature;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\Review;
use App\Services\Yandex\PersistParsedOrganization;
use App\Services\Yandex\YandexMapsParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YandexMapsParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_reviews_are_a_valid_success_payload(): void
    {
        config(['parser.url' => 'http://parser.test']);

        Http::fake([
            'http://parser.test/parse' => Http::response([
                'success' => true,
                'organization' => [
                    'businessId' => '156355253662',
                    'name' => 'Новое место',
                    'rating' => null,
                    'ratingsCount' => 0,
                    'reviewsCount' => 0,
                ],
                'reviews' => [],
                'meta' => [
                    'reviewsCollected' => 0,
                    'reviewsRequests' => 1,
                ],
            ]),
        ]);

        $parsed = app(YandexMapsParser::class)->parse('https://yandex.ru/maps/org/156355253662');

        $this->assertSame([], $parsed->reviews);
        $this->assertSame('156355253662', $parsed->organization->businessId);
        $this->assertSame(0, $parsed->organization->reviewsCount);
    }

    public function test_it_maps_node_review_contract_keys(): void
    {
        config(['parser.url' => 'http://parser.test']);

        Http::fake([
            'http://parser.test/parse' => Http::response([
                'success' => true,
                'organization' => [
                    'businessId' => '156355253662',
                    'name' => 'Цех',
                ],
                'reviews' => [[
                    'yandexReviewId' => 'review-1',
                    'author' => 'Иван',
                    'rating' => 5,
                    'text' => 'Отличное место',
                    'businessReply' => 'Спасибо',
                    'publishedAt' => '2026-08-20',
                ]],
            ]),
        ]);

        $parsed = app(YandexMapsParser::class)->parse('https://yandex.ru/maps/org/156355253662');
        $review = $parsed->reviews[0];

        $this->assertSame('review-1', $review->yandexReviewId);
        $this->assertSame('Спасибо', $review->businessReply);
        $this->assertSame('2026-08-20', $review->publishedAt);
    }

    public function test_node_payload_reviews_are_saved_to_the_database(): void
    {
        config(['parser.url' => 'http://parser.test']);

        Http::fake([
            'http://parser.test/parse' => Http::response([
                'success' => true,
                'organization' => [
                    'businessId' => '156355253662',
                    'name' => 'Цех',
                    'rating' => 4.8,
                    'ratingsCount' => 701,
                    'reviewsCount' => 2,
                    'aspects' => [
                        ['text' => 'Еда', 'count' => 10, 'positive' => 8, 'negative' => 2],
                    ],
                ],
                'reviews' => [[
                    'yandexReviewId' => 'review-1',
                    'author' => 'Иван',
                    'rating' => 5,
                    'text' => 'Отличное место',
                    'businessReply' => 'Спасибо',
                    'publishedAt' => '2026-08-20',
                ]],
            ]),
        ]);

        $organization = Organization::factory()->create([
            'yandex_business_id' => '156355253662',
            'url' => 'https://yandex.ru/maps/org/156355253662',
            'parse_status' => ParseStatus::Pending,
        ]);

        $parsed = app(YandexMapsParser::class)->parse($organization->url);
        app(PersistParsedOrganization::class)->persist($organization, $parsed);

        $this->assertSame(1, Review::query()->count());

        $review = Review::query()->first();
        $this->assertSame('review-1', $review->yandex_review_id);
        $this->assertSame('Иван', $review->author);
        $this->assertSame('Отличное место', $review->text);
        $this->assertSame('Спасибо', $review->business_reply);
        $this->assertSame('2026-08-20', $review->published_at?->toDateString());
        $this->assertSame(ParseStatus::Success, $organization->fresh()->parse_status);
        $this->assertSame('Еда', $organization->fresh()->aspects[0]['text']);
    }
}
