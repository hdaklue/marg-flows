<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
final class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'avatar' => fake()->imageUrl(200, 200, 'people'),
            'timezone' => fake()->randomElement([
                'America/New_York',
                'Europe/London',
                'Asia/Tokyo',
                'Australia/Sydney',
                'America/Los_Angeles',
                'Europe/Paris',
                'Asia/Dubai',
                'Africa/Cairo',
            ]),
        ];
    }
}
