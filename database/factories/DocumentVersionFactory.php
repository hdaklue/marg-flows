<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentVersion>
 */
final class DocumentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'content' => [
                'blocks' => [
                    [
                        'type' => 'paragraph',
                        'data' => [
                            'text' => $this->faker->paragraph(),
                        ],
                    ],
                ],
            ],
            'created_by' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Create a version with empty content.
     */
    public function empty(): static
    {
        return $this->state(fn(array $attributes) => [
            'content' => ['blocks' => []],
        ]);
    }

    /**
     * Create a version with complex content.
     */
    public function complex(): static
    {
        return $this->state(fn(array $attributes) => [
            'content' => [
                'blocks' => [
                    [
                        'type' => 'header',
                        'data' => [
                            'text' => $this->faker->sentence(),
                            'level' => 1,
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'data' => [
                            'text' => $this->faker->paragraph(),
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'data' => [
                            'text' => $this->faker->paragraph(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
