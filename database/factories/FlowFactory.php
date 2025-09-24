<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FlowStage;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flow>
 */
final class FlowFactory extends Factory
{
    protected $model = Flow::class;

    public function definition(): array
    {
        // Use controlled randomization instead of pure faker randomness
        $statusType = $this->getWeightedStatus();

        // Generate predictable dates based on status
        [$startDate, $dueDate, $completedAt] = $this->generateDatesForStatus($statusType);

        return [
            'title' => $this->getProjectTitle(),
            'description' => fake()->sentences(2, true),
            'stage' => $statusType,
            // 'start_date' => $startDate,
            // 'due_date' => $dueDate,
            'completed_at' => $completedAt,
            'settings' => $this->getProjectSettings(),
            'tenant_id' => Tenant::inRandomOrder()->first()->getKey(),
            'creator_id' => User::inRandomOrder()->first()->getKey(),
        ];
    }

    /**
     * Create scheduled flows (guaranteed future).
     */
    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            $now = now();
            $startDate = $now->copy()->addDays(fake()->numberBetween(1, 60))->startOfDay();
            $dueDate = $startDate->copy()->addDays(fake()->numberBetween(7, 45))->endOfDay();

            return [
                'status' => FlowStage::SCHEDULED->value,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Create active flows (guaranteed current).
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $now = now();
            $startDate = $now->copy()->subDays(fake()->numberBetween(1, 60))->startOfDay();
            $dueDate = $now->copy()->addDays(fake()->numberBetween(7, 60))->endOfDay();

            return [
                'status' => FlowStage::ACTIVE->value,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Create overdue flows (guaranteed past due).
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $now = now();
            $dueDate = $now->copy()->subDays(fake()->numberBetween(1, 30))->endOfDay();
            $startDate = $dueDate->copy()->subDays(fake()->numberBetween(14, 60))->startOfDay();

            return [
                'status' => FlowStage::ACTIVE->value, // Active but overdue
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Create paused flows.
     */
    public function paused(): static
    {
        return $this->state(function (array $attributes) {
            $now = now();
            $startDate = $now->copy()->subDays(fake()->numberBetween(7, 90))->startOfDay();
            $dueDate = $now->copy()->addDays(fake()->numberBetween(7, 45))->endOfDay();

            return [
                'status' => FlowStage::PAUSED->value,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Create completed flows (guaranteed finished).
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $now = now();
            $completedAt = $now->copy()->subDays(fake()->numberBetween(1, 90))->setTime(
                fake()->numberBetween(9, 17),
                fake()->numberBetween(0, 59),
                0,
            );

            $duration = fake()->numberBetween(7, 60);
            $startDate = $completedAt->copy()->subDays($duration)->startOfDay();

            // 80% completed on time, 20% late
            $dueOffset = fake()->boolean(80)
                ? fake()->numberBetween(0, 7)
                : fake()->numberBetween(-14, -1); // On time or early // Late

            $dueDate = $completedAt->copy()->addDays($dueOffset)->endOfDay();

            return [
                'status' => FlowStage::COMPLETED->value,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'completed_at' => $completedAt,
            ];
        });
    }

    /**
     * Create edge case flows for testing.
     */
    public function edgeCases(): static
    {
        return $this->state(function (array $attributes) {
            $now = now();

            $cases = [
                // Same day project
                'same_day' => [
                    'start_date' => $now->copy()->startOfDay(),
                    'due_date' => $now->copy()->endOfDay(),
                    'status' => FlowStage::ACTIVE->value,
                ],
                // Very short project (3 days)
                'short' => [
                    'start_date' => $now->copy()->subDay()->startOfDay(),
                    'due_date' => $now->copy()->addDays(2)->endOfDay(),
                    'status' => FlowStage::ACTIVE->value,
                ],
                // Started today
                'started_today' => [
                    'start_date' => $now->copy()->startOfDay(),
                    'due_date' => $now->copy()->addDays(14)->endOfDay(),
                    'status' => FlowStage::ACTIVE->value,
                ],
                // Due today
                'due_today' => [
                    'start_date' => $now->copy()->subDays(14)->startOfDay(),
                    'due_date' => $now->copy()->endOfDay(),
                    'status' => FlowStage::ACTIVE->value,
                ],
            ];

            $caseType = fake()->randomElement(array_keys($cases));

            return $cases[$caseType];
        });
    }

    /**
     * Get weighted status selection (more predictable than random).
     */
    private function getWeightedStatus(): int|string
    {
        $weights = [
            FlowStage::DRAFT->value => 25, // 25%
            FlowStage::ACTIVE->value => 45, // 45%
            FlowStage::COMPLETED->value => 20, // 20%
            FlowStage::PAUSED->value => 10, // 10%
        ];

        $rand = fake()->numberBetween(1, 100);
        $cumulative = 0;

        foreach ($weights as $status => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return FlowStage::ACTIVE->value; // Fallback
    }

    /**
     * Generate predictable dates based on status.
     */
    private function generateDatesForStatus(string|int $status): array
    {
        $now = now();

        return match ($status) {
            FlowStage::DRAFT->value => $this->scheduledDates($now),
            FlowStage::ACTIVE->value => $this->activeDates($now),
            FlowStage::PAUSED->value => $this->pausedDates($now),
            FlowStage::COMPLETED->value => $this->completedDates($now),
            default => $this->activeDates($now),
        };
    }

    /**
     * Generate dates for scheduled flows (predictable future dates).
     */
    private function scheduledDates(Carbon $now): array
    {
        // Start 1-90 days in the future
        $daysFromNow = fake()->numberBetween(1, 90);
        $startDate = $now->copy()->addDays($daysFromNow)->startOfDay();

        // Duration 7-60 days
        $duration = fake()->numberBetween(7, 60);
        $dueDate = $startDate->copy()->addDays($duration)->endOfDay();

        return [$startDate, $dueDate, null];
    }

    /**
     * Generate dates for active flows (predictable past start, future due).
     */
    private function activeDates(Carbon $now): array
    {
        // Started 1-120 days ago
        $daysAgo = fake()->numberBetween(1, 120);
        $startDate = $now->copy()->subDays($daysAgo)->startOfDay();

        // Due in 7-90 days from start (ensuring it's reasonable)
        $duration = fake()->numberBetween(14, 90);
        $dueDate = $startDate->copy()->addDays($duration)->endOfDay();

        // Ensure due date is in the future for most active flows
        if ($dueDate->lt($now) && fake()->boolean(70)) {
            // 70% chance to push due date to future if it's in the past
            $dueDate = $now->copy()->addDays(fake()->numberBetween(7, 30))->endOfDay();
        }

        return [$startDate, $dueDate, null];
    }

    /**
     * Generate dates for paused flows (started in past, not completed).
     */
    private function pausedDates(Carbon $now): array
    {
        // Started 7-180 days ago
        $daysAgo = fake()->numberBetween(7, 180);
        $startDate = $now->copy()->subDays($daysAgo)->startOfDay();

        // Due date is usually in the future (paused projects often get extended)
        $futureDays = fake()->numberBetween(7, 60);
        $dueDate = $now->copy()->addDays($futureDays)->endOfDay();

        return [$startDate, $dueDate, null];
    }

    /**
     * Generate dates for completed flows (realistic completion scenarios).
     */
    private function completedDates(Carbon $now): array
    {
        // Completed 1-180 days ago
        $completedDaysAgo = fake()->numberBetween(1, 180);
        $completedAt = $now->copy()->subDays($completedDaysAgo)->setTime(
            fake()->numberBetween(9, 17),
            fake()->numberBetween(0, 59),
            0,
        ); // Business hours

        // Started before completion
        $projectDuration = fake()->numberBetween(7, 90);
        $startDate = $completedAt->copy()->subDays($projectDuration)->startOfDay();

        // Due date - 80% completed on time, 20% completed late
        if (fake()->boolean(80)) {
            // Completed on time or early
            $dueDays = fake()->numberBetween($projectDuration, $projectDuration + 7);
            $dueDate = $startDate->copy()->addDays($dueDays)->endOfDay();
        } else {
            // Completed late
            $dueDays = fake()->numberBetween($projectDuration - 7, $projectDuration - 1);
            $dueDate = $startDate->copy()->addDays(max(7, $dueDays))->endOfDay();
        }

        return [$startDate, $dueDate, $completedAt];
    }

    /**
     * Get predictable project titles.
     */
    private function getProjectTitle(): string
    {
        $titles = [
            'Brand Identity Design',
            'Website Redesign',
            'Marketing Campaign',
            'Product Launch',
            'Digital Strategy',
            'Content Creation',
            'Logo Design',
            'E-commerce Setup',
            'Social Media Strategy',
            'Mobile App Design',
            'SEO Optimization',
            'Database Migration',
            'User Experience Audit',
            'Performance Optimization',
            'Security Assessment',
            'API Integration',
            'Payment Gateway Setup',
            'Customer Portal',
            'Analytics Implementation',
            'Email Campaign',
        ];

        return fake()->randomElement($titles);
    }

    /**
     * Get predictable project settings.
     */
    private function getProjectSettings(): null|array
    {
        if (fake()->boolean(30)) {
            return null; // 30% chance of no settings
        }

        return [
            'auto_assign' => fake()->boolean(60), // 60% chance true
            'client_access' => fake()->boolean(40), // 40% chance true
            'notifications' => fake()->randomElement([
                'all',
                'mentions',
                'none',
            ]),
            'budget' => fake()->boolean(70) ? fake()->numberBetween(5000, 50000) : null,
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
        ];
    }
}
