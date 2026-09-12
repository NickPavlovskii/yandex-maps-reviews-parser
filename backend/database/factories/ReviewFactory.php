<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'yandex_review_id' => fake()->unique()->regexify('[A-Za-z0-9_-]{24}'),
            'author' => fake()->name(),
            'rating' => fake()->numberBetween(1, 5),
            'text' => fake()->paragraph(),
            'business_reply' => null,
            'published_at' => fake()->dateTimeBetween('-2 years'),
        ];
    }
}
