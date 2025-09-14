<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Deliverable\DeliverableFormat;
use App\Enums\Deliverable\DeliverableStatus;
use App\Models\Deliverable;
use App\Models\Flow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deliverable>
 */
final class DeliverableFactory extends Factory
{
    protected $model = Deliverable::class;

    public function definition(): array
    {
        $format = fake()->randomElement(DeliverableFormat::cases());
        $type = $this->getRandomTypeForFormat($format);
        $formatSpecs = config("deliverables.{$format->value}.{$type}", []);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'format' => $format,
            'type' => $type,
            'status' => fake()->randomElement(DeliverableStatus::cases()),
            'priority' => fake()->numberBetween(1, 5),
            'order_column' => fake()->numberBetween(0, 100),
            'start_date' => fake()
                ->optional(0.7)
                ->dateTimeBetween('-1 month', '+1 week'),
            'success_date' => fake()
                ->optional(0.8)
                ->dateTimeBetween('+1 week', '+3 months'),
            'completed_at' => fake()
                ->optional(0.3)
                ->dateTimeBetween('-2 weeks', 'now'),
            'format_specifications' => $formatSpecs,
            'settings' => fake()
                ->optional(0.4)
                ->randomElements([
                    'auto_save' => true,
                    'notifications' => fake()->boolean(),
                    'quality_check' => fake()->boolean(),
                    'backup_enabled' => true,
                ]),
            'flow_id' => Flow::factory(),
            'creator_id' => User::factory(),
            'tenant_id' => fn (array $attributes) => Flow::find(
                $attributes['flow_id'],
            )->tenant_id,
        ];
    }

    public function design(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => DeliverableFormat::DESIGN,
            'type' => fake()->randomElement([
                'video_cover',
                'square',
                'story',
                'Portrait',
                'land_scape',
            ]),
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => DeliverableFormat::VIDEO,
            'type' => fake()->randomElement(['promotional', 'tutorial']),
        ]);
    }

    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => DeliverableFormat::AUDIO,
            'type' => fake()->randomElement(['podcast_episode', 'voiceover']),
        ]);
    }

    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => DeliverableFormat::DOCUMENT,
            'type' => fake()->randomElement(['project_brief']),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliverableStatus::DRAFT,
            'completed_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliverableStatus::IN_PROGRESS,
            'start_date' => fake()->dateTimeBetween('-1 week', 'now'),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliverableStatus::COMPLETED,
            'completed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(4, 5),
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(1, 2),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'success_date' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
            'status' => fake()->randomElement([
                DeliverableStatus::DRAFT,
                DeliverableStatus::IN_PROGRESS,
                DeliverableStatus::REVIEW,
            ]),
            'completed_at' => null,
        ]);
    }

    private function getRandomTypeForFormat(DeliverableFormat $format): string
    {
        return match ($format) {
            DeliverableFormat::DESIGN => fake()->randomElement([
                'video_cover',
                'square',
                'story',
                'Portrait',
                'land_scape',
            ]),
            DeliverableFormat::VIDEO => fake()->randomElement([
                'promotional',
                'tutorial',
            ]),
            DeliverableFormat::AUDIO => fake()->randomElement([
                'podcast_episode',
                'voiceover',
            ]),
            DeliverableFormat::DOCUMENT => fake()->randomElement([
                'project_brief',
            ]),
        };
    }
}
