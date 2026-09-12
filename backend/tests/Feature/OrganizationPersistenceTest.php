<?php

namespace Tests\Feature;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\Review;
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
}
