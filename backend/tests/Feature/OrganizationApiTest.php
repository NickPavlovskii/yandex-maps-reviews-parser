<?php

namespace Tests\Feature;

use App\Enums\ParseStatus;
use App\Models\Organization;
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

    public function test_it_returns_not_found_for_unknown_organization(): void
    {
        $this->getJson('/api/organizations/999')->assertNotFound();
        $this->getJson('/api/organizations/999/reviews')->assertNotFound();
    }
}
