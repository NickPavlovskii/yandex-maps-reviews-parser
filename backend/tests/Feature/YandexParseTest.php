<?php

namespace Tests\Feature;

use App\Contracts\MapsParser;
use App\Enums\ParseStatus;
use App\Jobs\ParseOrganizationReviewsJob;
use App\Models\Organization;
use App\Models\Review;
use App\Services\Yandex\DTO\OrganizationData;
use App\Services\Yandex\DTO\ParsedOrganization;
use App\Services\Yandex\DTO\ReviewData;
use App\Services\Yandex\Exceptions\ParserStructureChangedException;
use App\Services\Yandex\YandexParserException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class YandexParseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_parsing_for_any_yandex_maps_url(): void
    {
        Queue::fake();

        $url = 'https://yandex.ru/maps/970/novorossiysk/?ll=37.783460%2C44.713693&mode=poi&poi%5Bpoint%5D=37.781069%2C44.714909&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D156355253662&tab=reviews&z=15.93';

        $this->mock(MapsParser::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('parse');
        });

        $this->postJson('/api/organizations', ['url' => $url])
            ->assertAccepted()
            ->assertJsonPath('data.yandex_business_id', '156355253662')
            ->assertJsonPath('data.parse_status', ParseStatus::Pending->value);

        $this->assertSame(1, Organization::query()->count());
        Queue::assertPushed(ParseOrganizationReviewsJob::class);
    }

    public function test_legacy_yandex_parse_alias_only_queues_a_job(): void
    {
        Queue::fake();

        $this->mock(MapsParser::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('parse');
        });

        $this->postJson('/api/yandex/parse', [
            'url' => 'https://yandex.ru/maps/org/156355253662',
        ])
            ->assertAccepted()
            ->assertJsonPath('data.parse_status', ParseStatus::Pending->value);

        Queue::assertPushed(ParseOrganizationReviewsJob::class);
    }

    public function test_sync_parse_is_hidden_when_disabled(): void
    {
        config(['parser.sync_enabled' => false]);

        $this->postJson('/api/organizations/parse', [
            'url' => 'https://google.com/not-maps',
        ])->assertNotFound();
    }

    public function test_it_queues_parsing_for_a_different_organization_url(): void
    {
        Queue::fake();

        $this->postJson('/api/organizations', [
            'url' => 'https://yandex.ru/maps/org/gum/1074720998/reviews',
        ])
            ->assertAccepted()
            ->assertJsonPath('data.yandex_business_id', '1074720998');

        Queue::assertPushed(ParseOrganizationReviewsJob::class);
    }

    public function test_it_rejects_maps_urls_without_an_organization_id(): void
    {
        Queue::fake();

        $this->postJson('/api/organizations', [
            'url' => 'https://yandex.ru/maps/213/moscow/',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        $this->postJson('/api/yandex/parse', [
            'url' => 'https://yandex.ru/maps/-/CHHD5XYs',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        Queue::assertNothingPushed();
        $this->assertSame(0, Organization::query()->count());
    }

    public function test_it_does_not_create_duplicate_organizations(): void
    {
        Queue::fake();

        $payload = ['url' => 'https://yandex.ru/maps/org/156355253662'];

        $this->postJson('/api/organizations', $payload)->assertAccepted();
        $this->postJson('/api/organizations', $payload)->assertAccepted();

        $this->assertSame(1, Organization::query()->count());
        Queue::assertPushed(ParseOrganizationReviewsJob::class, 1);
    }

    public function test_it_rejects_non_yandex_maps_urls(): void
    {
        Queue::fake();

        $this->postJson('/api/organizations', [
            'url' => 'https://google.com/maps/place/cafe',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        Queue::assertNothingPushed();
    }

    public function test_it_parses_a_url_and_returns_the_payload_saved_to_the_database(): void
    {
        $url = 'https://yandex.ru/maps/org/156355253662';

        $this->mock(MapsParser::class, function (MockInterface $mock) use ($url): void {
            $mock->shouldReceive('parse')
                ->once()
                ->with($url)
                ->andReturn(new ParsedOrganization(
                    organization: new OrganizationData(
                        businessId: '156355253662',
                        name: 'Цех',
                        rating: 4.8,
                        ratingsCount: 701,
                        reviewsCount: 623,
                    ),
                    reviews: [
                        ReviewData::fromArray([
                            'yandexReviewId' => 'review-1',
                            'author' => 'Иван',
                            'rating' => 5,
                            'text' => 'Отличное место',
                            'businessReply' => null,
                            'publishedAt' => '2026-08-20',
                        ]),
                    ],
                    meta: [],
                ));
        });

        $this->postJson('/api/organizations/parse', ['url' => $url])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.organization.yandex_business_id', '156355253662')
            ->assertJsonPath('data.organization.name', 'Цех')
            ->assertJsonPath('data.organization.avg_rating', 4.8)
            ->assertJsonPath('data.organization.ratings_count', 701)
            ->assertJsonPath('data.organization.reviews_count', 623)
            ->assertJsonPath('data.organization.parse_status', 'success')
            ->assertJsonPath('data.reviews.0.author', 'Иван')
            ->assertJsonPath('data.reviews.0.published_at', '2026-08-20')
            ->assertJsonPath('data.pagination.per_page', 50)
            ->assertJsonPath('data.pagination.total', 1);

        $this->assertSame(1, Organization::query()->count());
        $this->assertSame(1, Review::query()->count());
    }

    public function test_it_saves_an_organization_without_reviews(): void
    {
        $url = 'https://yandex.ru/maps/org/156355253662';

        $this->mock(MapsParser::class, function (MockInterface $mock) use ($url): void {
            $mock->shouldReceive('parse')
                ->once()
                ->with($url)
                ->andReturn(new ParsedOrganization(
                    organization: new OrganizationData(
                        businessId: '156355253662',
                        name: 'Новое место',
                        rating: null,
                        ratingsCount: 0,
                        reviewsCount: 0,
                    ),
                    reviews: [],
                    meta: [],
                ));
        });

        $this->postJson('/api/organizations/parse', ['url' => $url])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.organization.parse_status', 'success')
            ->assertJsonPath('data.pagination.total', 0);

        $this->assertSame(1, Organization::query()->count());
        $this->assertSame(0, Review::query()->count());
    }

    public function test_it_returns_parser_error_without_saving_reviews(): void
    {
        $this->mock(MapsParser::class, function (MockInterface $mock): void {
            $mock->shouldReceive('parse')
                ->once()
                ->andThrow(new YandexParserException('Yandex returned no reviews.'));
        });

        $this->postJson('/api/organizations/parse', [
            'url' => 'https://yandex.ru/maps/org/156355253662',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertSame(0, Review::query()->count());
    }

    public function test_sync_parse_keeps_structure_changed_status(): void
    {
        $this->mock(MapsParser::class, function (MockInterface $mock): void {
            $mock->shouldReceive('parse')
                ->once()
                ->andThrow(new ParserStructureChangedException('Yandex returned no fetchReviews responses.'));
        });

        $this->postJson('/api/organizations/parse', [
            'url' => 'https://yandex.ru/maps/org/156355253662',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('organizations', [
            'yandex_business_id' => '156355253662',
            'parse_status' => ParseStatus::FailedStructureChanged->value,
        ]);
    }
}
