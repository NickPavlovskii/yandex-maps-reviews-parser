<?php

namespace Tests\Feature;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\Review;
use App\Services\Yandex\DTO\OrganizationData;
use App\Services\Yandex\DTO\ParsedOrganization;
use App\Services\Yandex\DTO\ReviewData;
use App\Services\Yandex\PersistParsedOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_are_upserted_by_yandex_review_id(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Pending,
        ]);

        Review::updateOrCreate(
            ['yandex_review_id' => 'LvDp2rjgX9Z8-EKH7rMr6QGee7D1FDgH'],
            [
                'organization_id' => $organization->id,
                'author' => 'Иван',
                'rating' => 5,
                'text' => 'Отличное место',
            ],
        );

        Review::updateOrCreate(
            ['yandex_review_id' => 'LvDp2rjgX9Z8-EKH7rMr6QGee7D1FDgH'],
            [
                'organization_id' => $organization->id,
                'author' => 'Иван',
                'rating' => 4,
                'text' => 'Обновлённый текст',
            ],
        );

        $this->assertSame(1, Review::query()->count());
        $this->assertDatabaseHas('reviews', [
            'yandex_review_id' => 'LvDp2rjgX9Z8-EKH7rMr6QGee7D1FDgH',
            'rating' => 4,
            'text' => 'Обновлённый текст',
        ]);
    }

    public function test_organizations_are_unique_by_yandex_business_id(): void
    {
        $first = Organization::query()->firstOrCreate(
            ['yandex_business_id' => '156355253662'],
            [
                'url' => 'https://yandex.ru/maps/org/156355253662',
                'parse_status' => ParseStatus::Pending,
            ],
        );

        $second = Organization::query()->firstOrCreate(
            ['yandex_business_id' => '156355253662'],
            [
                'url' => 'https://yandex.ru/maps/org/tsekh/156355253662',
                'parse_status' => ParseStatus::Pending,
            ],
        );

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Organization::query()->count());
    }

    public function test_it_computes_rating_from_reviews_when_yandex_omits_counters(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Pending,
        ]);

        app(PersistParsedOrganization::class)->persist($organization, new ParsedOrganization(
            new OrganizationData($organization->yandex_business_id, 'Цех', null, null, null),
            [
                ReviewData::fromArray([
                    'yandexReviewId' => 'r-1',
                    'author' => 'Анна',
                    'rating' => 5,
                    'text' => 'Ок',
                ]),
                ReviewData::fromArray([
                    'yandexReviewId' => 'r-2',
                    'author' => 'Олег',
                    'rating' => 4,
                    'text' => 'Норм',
                ]),
            ],
            [],
        ));

        $organization->refresh();

        $this->assertSame(4.5, (float) $organization->avg_rating);
        $this->assertSame(2, $organization->ratings_count);
        $this->assertSame(2, $organization->reviews_count);
    }
}
