<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeedbackStatus;
use App\Models\Document;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
final class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'content' => fake()->paragraph(),
            'metadata' => $this->createDocumentBlockMetadata(),
            'feedbackable_type' => Relation::getMorphAlias(Document::class),
            'feedbackable_id' => fn () => Document::inRandomOrder()->first()?->id ?? Document::factory()->create()->id,
            'status' => fake()->randomElement(FeedbackStatus::cases()),
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function documentBlock(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $this->createDocumentBlockMetadata(),
        ]);
    }

    public function audioRegion(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $this->createAudioRegionMetadata(),
        ]);
    }

    public function videoRegion(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $this->createVideoRegionMetadata(),
        ]);
    }

    public function videoFrame(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $this->createVideoFrameMetadata(),
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::OPEN,
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::RESOLVED,
            'resolution' => fake()->sentence(),
            'resolved_by' => User::factory(),
            'resolved_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::URGENT,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::IN_PROGRESS,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::REJECTED,
            'resolution' => 'Feedback was rejected: ' . fake()->sentence(),
            'resolved_by' => User::factory(),
            'resolved_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function forPage(Document $page): static
    {
        return $this->state(fn (array $attributes) => [
            'feedbackable_type' => Relation::getMorphAlias(Document::class),
            'feedbackable_id' => $page->id,
        ]);
    }

    public function byCreator(User $creator): static
    {
        return $this->state(fn (array $attributes) => [
            'creator_id' => $creator->id,
        ]);
    }

    private function createDocumentBlockMetadata(): array
    {
        return [
            'type' => 'document_block',
            'data' => [
                'block_id' => 'block_' . fake()->uuid(),
                'block_type' => fake()->randomElement(['paragraph', 'header', 'nestedList', 'table', 'alert']),
                'block_index' => fake()->numberBetween(0, 20),
            ],
            'searchable' => [
                'block_type' => fake()->randomElement(['paragraph', 'header', 'nestedList', 'table', 'alert']),
                'block_index' => fake()->numberBetween(0, 20),
            ],
        ];
    }

    private function createAudioRegionMetadata(): array
    {
        $startTime = fake()->randomFloat(2, 0, 300);
        $endTime = $startTime + fake()->randomFloat(2, 1, 30);

        return [
            'type' => 'audio_region',
            'data' => [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $endTime - $startTime,
                'timing' => [
                    'start' => [
                        'seconds' => $startTime,
                        'formatted' => $this->formatTime($startTime),
                    ],
                    'end' => [
                        'seconds' => $endTime,
                        'formatted' => $this->formatTime($endTime),
                    ],
                ],
            ],
            'searchable' => [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $endTime - $startTime,
            ],
        ];
    }

    private function createVideoRegionMetadata(): array
    {
        $startTime = fake()->randomFloat(2, 0, 300);
        $endTime = $startTime + fake()->randomFloat(2, 1, 30);
        $frameRate = fake()->randomElement([24.0, 25.0, 30.0, 60.0]);

        return [
            'type' => 'video_region',
            'data' => [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $endTime - $startTime,
                'frame_rate' => $frameRate,
                'bounds' => [
                    'x' => fake()->numberBetween(0, 1000),
                    'y' => fake()->numberBetween(0, 1000),
                    'width' => fake()->numberBetween(100, 500),
                    'height' => fake()->numberBetween(100, 500),
                ],
                'timing' => [
                    'start' => [
                        'seconds' => $startTime,
                        'formatted' => $this->formatTime($startTime),
                        'frame' => (int) ($startTime * $frameRate),
                    ],
                    'end' => [
                        'seconds' => $endTime,
                        'formatted' => $this->formatTime($endTime),
                        'frame' => (int) ($endTime * $frameRate),
                    ],
                ],
            ],
            'searchable' => [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $endTime - $startTime,
                'frame_rate' => $frameRate,
            ],
        ];
    }

    private function createVideoFrameMetadata(): array
    {
        $frameRate = fake()->randomElement([24.0, 25.0, 30.0, 60.0]);
        $frameNumber = fake()->numberBetween(0, 7200); // Up to 5 minutes at 24fps
        $time = $frameNumber / $frameRate;

        return [
            'type' => 'video_frame',
            'data' => [
                'time' => $time,
                'frame_number' => $frameNumber,
                'frame_rate' => $frameRate,
                'timing' => [
                    'seconds' => $time,
                    'formatted' => $this->formatTime($time),
                    'frame' => $frameNumber,
                ],
            ],
            'searchable' => [
                'time' => $time,
                'frame_number' => $frameNumber,
                'frame_rate' => $frameRate,
            ],
        ];
    }

    private function formatTime(float $seconds): string
    {
        $minutes = (int) ($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%05.2f', $minutes, $remainingSeconds);
    }
}
