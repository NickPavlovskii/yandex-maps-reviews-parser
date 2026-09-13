<?php

namespace Tests\Feature;

use App\Enums\ParseRunStatus;
use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\ParseRun;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    public function test_guest_cannot_read_organizations(): void
    {
        auth()->logout();
        $organization = Organization::factory()->create();
        $this->getJson('/api/organizations/'.$organization->id)->assertUnauthorized();
        $this->getJson('/api/organizations/'.$organization->id.'/reviews')->assertUnauthorized();
    }

    public function test_it_returns_organization_from_the_database(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Цех',
            'avg_rating' => 4.80,
            'ratings_count' => 701,
            'reviews_count' => 623,
            'parse_status' => ParseStatus::Success,
        ]);

        $this->getJson('/api/organizations/'.$organization->id)
            ->assertOk()
            ->assertJsonPath('data.id', $organization->id)
            ->assertJsonPath('data.name', 'Цех')
            ->assertJsonPath('data.parse_status', 'success')
            ->assertJsonPath('data.reviews_count', 623)
            ->assertJsonMissingPath('data.reviews');
    }

    public function test_it_paginates_reviews_from_the_database(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Success,
        ]);

        Review::factory()
            ->count(52)
            ->for($organization)
            ->create();

        $this->getJson('/api/organizations/'.$organization->id.'/reviews?page=1&per_page=50')
            ->assertOk()
            ->assertJsonCount(50, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 50)
            ->assertJsonPath('meta.total', 52)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/organizations/'.$organization->id.'/reviews?page=2&per_page=50')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_it_fills_rating_from_reviews_when_parser_left_them_empty(): void
    {
        $organization = Organization::factory()->create([
            'avg_rating' => null,
            'ratings_count' => null,
            'reviews_count' => 2,
            'parse_status' => ParseStatus::Success,
        ]);

        Review::factory()->for($organization)->create(['rating' => 5]);
        Review::factory()->for($organization)->create(['rating' => 4]);

        $this->getJson('/api/organizations/'.$organization->id)
            ->assertOk()
            ->assertJsonPath('data.avg_rating', 4.5)
            ->assertJsonPath('data.ratings_count', 2);
    }

    public function test_it_returns_not_found_for_unknown_organization(): void
    {
        $this->getJson('/api/organizations/999')->assertNotFound();
        $this->getJson('/api/organizations/999/reviews')->assertNotFound();
    }

    public function test_it_returns_rating_breakdown_and_parse_duration(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Success,
        ]);

        ParseRun::query()->create([
            'organization_id' => $organization->id,
            'status' => ParseRunStatus::Success,
            'started_at' => now()->subSeconds(72),
            'finished_at' => now(),
        ]);

        Review::factory()->for($organization)->count(3)->create(['rating' => 5]);
        Review::factory()->for($organization)->count(2)->create(['rating' => 4]);

        $this->getJson('/api/organizations/'.$organization->id)
            ->assertOk()
            ->assertJsonPath('data.rating_breakdown.0.rating', 5)
            ->assertJsonPath('data.rating_breakdown.0.count', 3)
            ->assertJsonPath('data.rating_breakdown.1.rating', 4)
            ->assertJsonPath('data.rating_breakdown.1.count', 2)
            ->assertJsonPath('data.rating_breakdown.4.rating', 1)
            ->assertJsonPath('data.rating_breakdown.4.count', 0)
            ->assertJsonPath('data.last_parse_duration_seconds', 72);
    }

    public function test_it_returns_aspects_and_rating_history(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Success,
            'aspects' => [
                ['text' => 'Еда', 'count' => 1389, 'positive' => 1080, 'negative' => 263],
            ],
        ]);

        $first = $organization->snapshots()->create([
            'avg_rating' => 4.38,
            'ratings_count' => 500,
            'reviews_count' => 521,
        ]);
        $first->forceFill(['created_at' => '2026-06-14 10:00:00'])->save();

        $second = $organization->snapshots()->create([
            'avg_rating' => 4.60,
            'ratings_count' => 610,
            'reviews_count' => 612,
        ]);
        $second->forceFill(['created_at' => '2026-09-12 09:41:00'])->save();

        $this->getJson('/api/organizations/'.$organization->id)
            ->assertOk()
            ->assertJsonPath('data.aspects.0.text', 'Еда')
            ->assertJsonPath('data.aspects.0.negative', 263)
            ->assertJsonPath('data.rating_history.0.avg_rating', 4.38)
            ->assertJsonPath('data.rating_history.1.reviews_count', 612);
    }

    public function test_it_filters_and_sorts_reviews(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Success,
        ]);

        Review::factory()->for($organization)->create([
            'author' => 'Марина',
            'rating' => 5,
            'text' => 'Лучший фильтр в районе',
            'published_at' => '2026-09-09',
        ]);

        Review::factory()->for($organization)->create([
            'author' => 'Пётр',
            'rating' => 2,
            'text' => 'Долго ждали завтрак',
            'published_at' => '2026-09-08',
        ]);

        $this->getJson('/api/organizations/'.$organization->id.'/reviews?rating=5')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.author', 'Марина');

        $this->getJson('/api/organizations/'.$organization->id.'/reviews?q=завтрак')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.author', 'Пётр');

        $this->getJson('/api/organizations/'.$organization->id.'/reviews?sort=oldest')
            ->assertOk()
            ->assertJsonPath('data.0.author', 'Пётр')
            ->assertJsonPath('data.1.author', 'Марина');
    }
}
