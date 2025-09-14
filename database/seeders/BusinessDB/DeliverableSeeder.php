<?php

declare(strict_types=1);

namespace Database\Seeders\BusinessDB;

use App\Enums\Deliverable\DeliverableFormat;
use App\Enums\Deliverable\DeliverableStatus;
use App\Enums\Role\AssigneeRole;
use App\Models\Deliverable;
use App\Models\Flow;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DeliverableSeeder extends Seeder
{
    public function run(): void
    {
        $flows = Flow::with('tenant')->take(20)->get();

        foreach ($flows as $flow) {
            $this->createDeliverablesForFlow($flow);
        }
    }

    private function createDeliverablesForFlow(Flow $flow): void
    {
        $users = User::whereHas('tenantMemberships', fn ($query) => $query->where(
            'tenant_id',
            $flow->tenant_id,
        ))->take(5)->get();

        if ($users->isEmpty()) {
            return;
        }

        // Create design deliverables
        $this->createDesignDeliverables($flow, $users);

        // Create video deliverables
        $this->createVideoDeliverables($flow, $users);

        // Create audio deliverables
        $this->createAudioDeliverables($flow, $users);

        // Create document deliverables
        $this->createDocumentDeliverables($flow, $users);
    }

    private function createDesignDeliverables(Flow $flow, $users): void
    {
        $designTypes = [
            'video_cover',
            'square',
            'story',
            'Portrait',
            'land_scape',
        ];

        foreach (fake()->randomElements($designTypes, fake()->numberBetween(
            1,
            3,
        )) as $type) {
            $deliverable = Deliverable::create([
                'title' => $this->getDesignTitle($type),
                'description' => $this->getDesignDescription($type),
                'format' => DeliverableFormat::DESIGN,
                'type' => $type,
                'status' => fake()->randomElement(DeliverableStatus::cases()),
                'priority' => fake()->numberBetween(1, 5),
                'order_column' => fake()->numberBetween(1, 10),
                'start_date' => fake()
                    ->optional(0.7)
                    ->dateTimeBetween('-2 weeks', '+1 week'),
                'success_date' => fake()
                    ->optional(0.8)
                    ->dateTimeBetween('+1 week', '+2 months'),
                'format_specifications' => config(
                    "deliverables.design.{$type}",
                    [],
                ),
                'settings' => [
                    'auto_save' => true,
                    'notifications' => fake()->boolean(),
                    'quality_check' => true,
                ],
                'flow_id' => $flow->id,
                'creator_id' => $flow->creator_id,
                'tenant_id' => $flow->tenant_id,
            ]);

            $this->assignParticipants($deliverable, $users);
        }
    }

    private function createVideoDeliverables(Flow $flow, $users): void
    {
        $videoTypes = ['promotional', 'tutorial'];

        foreach (fake()->randomElements($videoTypes, fake()->numberBetween(
            1,
            2,
        )) as $type) {
            $deliverable = Deliverable::create([
                'title' => $this->getVideoTitle($type),
                'description' => $this->getVideoDescription($type),
                'format' => DeliverableFormat::VIDEO,
                'type' => $type,
                'status' => fake()->randomElement(DeliverableStatus::cases()),
                'priority' => fake()->numberBetween(2, 5),
                'order_column' => fake()->numberBetween(1, 10),
                'start_date' => fake()
                    ->optional(0.7)
                    ->dateTimeBetween('-2 weeks', '+1 week'),
                'success_date' => fake()
                    ->optional(0.8)
                    ->dateTimeBetween('+2 weeks', '+3 months'),
                'format_specifications' => config(
                    "deliverables.video.{$type}",
                    [],
                ),
                'settings' => [
                    'auto_save' => true,
                    'backup_enabled' => true,
                    'quality_check' => true,
                ],
                'flow_id' => $flow->id,
                'creator_id' => $flow->creator_id,
                'tenant_id' => $flow->tenant_id,
            ]);

            $this->assignParticipants($deliverable, $users);
        }
    }

    private function createAudioDeliverables(Flow $flow, $users): void
    {
        if (fake()->boolean(30)) { // 30% chance of audio deliverables
            $audioTypes = ['podcast_episode', 'voiceover'];
            $type = fake()->randomElement($audioTypes);

            $deliverable = Deliverable::create([
                'title' => $this->getAudioTitle($type),
                'description' => $this->getAudioDescription($type),
                'format' => DeliverableFormat::AUDIO,
                'type' => $type,
                'status' => fake()->randomElement(DeliverableStatus::cases()),
                'priority' => fake()->numberBetween(1, 4),
                'order_column' => fake()->numberBetween(1, 10),
                'start_date' => fake()
                    ->optional(0.7)
                    ->dateTimeBetween('-2 weeks', '+1 week'),
                'success_date' => fake()
                    ->optional(0.8)
                    ->dateTimeBetween('+1 week', '+2 months'),
                'format_specifications' => config(
                    "deliverables.audio.{$type}",
                    [],
                ),
                'settings' => [
                    'auto_save' => true,
                    'quality_check' => true,
                ],
                'flow_id' => $flow->id,
                'creator_id' => $flow->creator_id,
                'tenant_id' => $flow->tenant_id,
            ]);

            $this->assignParticipants($deliverable, $users);
        }
    }

    private function createDocumentDeliverables(Flow $flow, $users): void
    {
        $deliverable = Deliverable::create([
            'title' => 'Project Brief Document',
            'description' => 'Comprehensive project brief outlining objectives, scope, and requirements',
            'format' => DeliverableFormat::DOCUMENT,
            'type' => 'project_brief',
            'status' => fake()->randomElement(DeliverableStatus::cases()),
            'priority' => 4, // High priority for project briefs
            'order_column' => 1, // Usually first deliverable
            'start_date' => fake()
                ->optional(0.9)
                ->dateTimeBetween('-1 week', 'now'),
            'success_date' => fake()
                ->optional(0.9)
                ->dateTimeBetween('+3 days', '+2 weeks'),
            'format_specifications' => config('deliverables.document.project_brief', [
            ]),
            'settings' => [
                'auto_save' => true,
                'version_control' => true,
                'approval_required' => true,
            ],
            'flow_id' => $flow->id,
            'creator_id' => $flow->creator_id,
            'tenant_id' => $flow->tenant_id,
        ]);

        $this->assignParticipants($deliverable, $users);
    }

    private function assignParticipants(Deliverable $deliverable, $users): void
    {
        $assignees = $users->random(fake()->numberBetween(1, 3));
        $reviewers = $users->random(fake()->numberBetween(1, 2));

        foreach ($assignees as $assignee) {
            $deliverable->assignParticipant(
                $assignee->id,
                AssigneeRole::ASSIGNEE,
            );
        }

        foreach ($reviewers as $reviewer) {
            $deliverable->assignParticipant(
                $reviewer->id,
                AssigneeRole::REVIEWER,
            );
        }
    }

    private function getDesignTitle(string $type): string
    {
        return match ($type) {
            'video_cover' => fake()->randomElement([
                'YouTube Channel Cover Design',
                'Video Thumbnail Design',
                'Social Media Cover Design',
            ]),
            'square' => fake()->randomElement([
                'Instagram Post Design',
                'Social Media Square Banner',
                'Profile Picture Design',
            ]),
            'story' => fake()->randomElement([
                'Instagram Story Template',
                'Social Media Story Design',
                'Mobile Story Layout',
            ]),
            'Portrait' => fake()->randomElement([
                'Portrait Layout Design',
                'Mobile App Screen Design',
                'Vertical Banner Design',
            ]),
            'land_scape' => fake()->randomElement([
                'Website Header Design',
                'Presentation Slide Design',
                'Landscape Banner Design',
            ]),
            default => 'Design Deliverable',
        };
    }

    private function getDesignDescription(string $type): string
    {
        return match ($type) {
            'video_cover' => 'Create an engaging video cover that captures attention and represents the brand effectively with proper 16:9 aspect ratio.',
            'square' => 'Design a balanced square format perfect for social media posts with clear messaging and brand consistency.',
            'story' => 'Develop a vertical story format optimized for mobile viewing with engaging visual hierarchy.',
            'Portrait' => 'Create a portrait-oriented design ideal for mobile applications and vertical content display.',
            'land_scape' => 'Design a wide landscape format perfect for presentations and web headers with cinematic appeal.',
            default => 'Custom design deliverable with specific requirements.',
        };
    }

    private function getVideoTitle(string $type): string
    {
        return match ($type) {
            'promotional' => fake()->randomElement([
                'Brand Promotional Video',
                'Product Launch Video',
                'Marketing Campaign Video',
            ]),
            'tutorial' => fake()->randomElement([
                'How-to Tutorial Video',
                'Educational Content Video',
                'Training Material Video',
            ]),
            default => 'Video Deliverable',
        };
    }

    private function getVideoDescription(string $type): string
    {
        return match ($type) {
            'promotional' => 'Create a compelling promotional video that showcases the brand and drives engagement within the specified duration.',
            'tutorial' => 'Develop an educational tutorial video with clear instructions, proper pacing, and engaging visual elements.',
            default => 'Professional video content with high production value.',
        };
    }

    private function getAudioTitle(string $type): string
    {
        return match ($type) {
            'podcast_episode' => fake()->randomElement([
                'Podcast Episode Recording',
                'Audio Content Episode',
                'Branded Podcast Content',
            ]),
            'voiceover' => fake()->randomElement([
                'Professional Voiceover',
                'Narration Recording',
                'Commercial Voiceover',
            ]),
            default => 'Audio Deliverable',
        };
    }

    private function getAudioDescription(string $type): string
    {
        return match ($type) {
            'podcast_episode' => 'Record a professional podcast episode with high audio quality, proper intro/outro, and engaging content.',
            'voiceover' => 'Create professional voiceover recording with clear diction, proper pacing, and studio-quality audio.',
            default => 'High-quality audio content with professional production standards.',
        };
    }
}
