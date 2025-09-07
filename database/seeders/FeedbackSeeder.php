<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Feedbacks\AudioFeedback;
use App\Models\Feedbacks\DocumentFeedback;
use App\Models\Feedbacks\VideoFeedback;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing users and pages for realistic relationships
        $users = User::limit(10)->get();
        $pages = Document::limit(5)->get();

        if ($users->isEmpty() || $pages->isEmpty()) {
            $this->command->warn(
                'No users or pages found. Please run UserSeeder and PageSeeder first.',
            );

            return;
        }

        $this->command->info('Creating feedback for pages...');

        foreach ($pages as $page) {
            // Create document block feedbacks for each page
            $this->createDocumentFeedbacks($page, $users);

            // Create some media feedbacks (if applicable)
            $this->createMediaFeedbacks($page, $users);
        }

        $this->command->info('Feedback seeding completed!');
    }

    private function createDocumentFeedbacks(Document $page, $users): void
    {
        // Create various document block feedbacks
        $feedbackTypes = [
            [
                'status' => 'open',
                'urgency' => 1,
                'content' => 'This paragraph needs more detail about the implementation.',
            ],
            [
                'status' => 'open',
                'urgency' => 3,
                'content' => 'Critical error in this code block - needs immediate attention!',
            ],
            [
                'status' => 'resolved',
                'urgency' => 1,
                'content' => 'Grammar issues in this header.',
            ],
            [
                'status' => 'running',
                'urgency' => 2,
                'content' => 'Working on restructuring this table for better readability.',
            ],
            [
                'status' => 'rejected',
                'urgency' => 1,
                'content' => 'Suggested removing this alert box.',
            ],
        ];

        foreach ($feedbackTypes as $index => $feedbackData) {
            $creator = $users->random();

            $feedbackData['id'] = Str::ulid();
            $feedbackData['creator_id'] = $creator->id;
            $feedbackData['feedbackable_type'] = $page->getMorphClass();
            $feedbackData['feedbackable_id'] = $page->id;
            $feedbackData['block_id'] = 'block-' . Str::random(8);
            $feedbackData['element_type'] = fake()->randomElement([
                'paragraph',
                'header',
                'list',
            ]);

            $feedback = DocumentFeedback::create($feedbackData);

            // If resolved or rejected, add resolver
            if (in_array($feedbackData['status'], ['resolved', 'rejected'])) {
                $resolver = $users->where('id', '!=', $creator->id)->random();
                $feedback->update([
                    'resolved_by' => $resolver->id,
                    'resolved_at' => now()->subDays(rand(1, 7)),
                    'resolution' => $feedbackData['status'] === 'resolved'
                        ? 'Fixed the issue as requested.'
                        : 'This change is not needed at this time.',
                ]);
            }
        }

        // Create some additional random document feedbacks
        for ($i = 0; $i < rand(3, 8); $i++) {
            DocumentFeedback::create([
                'id' => Str::ulid(),
                'creator_id' => $users->random()->id,
                'feedbackable_type' => $page->getMorphClass(),
                'feedbackable_id' => $page->id,
                'content' => fake()->sentence(),
                'status' => fake()->randomElement([
                    'open',
                    'running',
                    'resolved',
                ]),
                'urgency' => fake()->randomElement([1, 2, 3]),
                'block_id' => 'block-' . Str::random(8),
                'element_type' => fake()->randomElement([
                    'paragraph',
                    'header',
                    'list',
                ]),
            ]);
        }
    }

    private function createMediaFeedbacks(Document $page, $users): void
    {
        // Create some audio region feedbacks
        for ($i = 0; $i < rand(2, 5); $i++) {
            AudioFeedback::create([
                'id' => Str::ulid(),
                'creator_id' => $users->random()->id,
                'feedbackable_type' => $page->getMorphClass(),
                'feedbackable_id' => $page->id,
                'content' => fake()->randomElement([
                    'Audio quality is poor in this section.',
                    'Background noise is too loud here.',
                    'Speaker is unclear during this time range.',
                    'Volume needs to be adjusted.',
                    'Consider adding subtitles for this part.',
                ]),
                'status' => fake()->randomElement([
                    'open',
                    'running',
                    'resolved',
                ]),
                'urgency' => fake()->randomElement([1, 2, 3]),
                'start_time' => fake()->randomFloat(2, 0, 300),
                'end_time' => fake()->randomFloat(2, 300, 600),
            ]);
        }

        // Create some video region feedbacks
        for ($i = 0; $i < rand(1, 3); $i++) {
            VideoFeedback::create([
                'id' => Str::ulid(),
                'creator_id' => $users->random()->id,
                'feedbackable_type' => $page->getMorphClass(),
                'feedbackable_id' => $page->id,
                'content' => fake()->randomElement([
                    'Lighting needs adjustment in this area.',
                    'Color correction required for this region.',
                    'Subject is out of focus during this time.',
                    'Consider cropping this area out.',
                    'Graphics overlay needed here.',
                ]),
                'status' => fake()->randomElement([
                    'open',
                    'running',
                    'resolved',
                ]),
                'urgency' => fake()->randomElement([1, 2, 3]),
                'feedback_type' => 'region',
                'start_time' => fake()->randomFloat(2, 0, 300),
                'end_time' => fake()->randomFloat(2, 300, 600),
                'x_coordinate' => fake()->numberBetween(0, 1920),
                'y_coordinate' => fake()->numberBetween(0, 1080),
            ]);
        }

        // Create some video frame feedbacks
        for ($i = 0; $i < rand(1, 4); $i++) {
            VideoFeedback::create([
                'id' => Str::ulid(),
                'creator_id' => $users->random()->id,
                'feedbackable_type' => $page->getMorphClass(),
                'feedbackable_id' => $page->id,
                'content' => fake()->randomElement([
                    'This frame has a visual artifact.',
                    'Timestamp overlay is incorrect.',
                    'Frame transition is too abrupt.',
                    'Consider using this as a thumbnail.',
                    'Quality degradation visible in this frame.',
                ]),
                'status' => fake()->randomElement([
                    'open',
                    'running',
                    'resolved',
                ]),
                'urgency' => fake()->randomElement([1, 2, 3]),
                'feedback_type' => 'frame',
                'timestamp' => fake()->randomFloat(2, 0, 600),
                'x_coordinate' => fake()->numberBetween(0, 1920),
                'y_coordinate' => fake()->numberBetween(0, 1080),
            ]);
        }
    }
}
