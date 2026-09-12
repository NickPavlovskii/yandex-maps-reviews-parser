<?php

namespace Database\Factories;

use App\Enums\ParseStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessId = (string) fake()->unique()->numerify('##########');

        return [
            'url' => 'https://yandex.ru/maps/org/'.$businessId,
            'yandex_business_id' => $businessId,
            'name' => fake()->company(),
            'avg_rating' => null,
            'ratings_count' => null,
            'reviews_count' => null,
            'parse_status' => ParseStatus::Pending,
            'last_parsed_at' => null,
        ];
    }
}
