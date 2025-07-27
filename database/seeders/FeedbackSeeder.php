<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Seeder;

final class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing users and pages for realistic relationships
        $users = User::limit(10)->get();
        $pages = Document::limit(5)->get();

        if ($users->isEmpty() || $pages->isEmpty()) {
            $this->command->warn('No users or pages found. Please run UserSeeder and PageSeeder first.');

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
            ['status' => 'open', 'content' => 'This paragraph needs more detail about the implementation.'],
            ['status' => 'urgent', 'content' => 'Critical error in this code block - needs immediate attention!'],
            ['status' => 'resolved', 'content' => 'Grammar issues in this header.'],
            ['status' => 'in_progress', 'content' => 'Working on restructuring this table for better readability.'],
            ['status' => 'rejected', 'content' => 'Suggested removing this alert box.'],
        ];

        foreach ($feedbackTypes as $index => $feedbackData) {
            $creator = $users->random();

            $feedback = Feedback::factory()
                ->documentBlock()
                ->forPage($page)
                ->byCreator($creator)
                ->create([
                    'content' => $feedbackData['content'],
                    'status' => $feedbackData['status'],
                ]);

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
        Feedback::factory()
            ->count(rand(3, 8))
            ->documentBlock()
            ->forPage($page)
            ->create([
                'creator_id' => $users->random()->id,
            ]);
    }

    private function createMediaFeedbacks(Document $page, $users): void
    {
        // Create some audio region feedbacks
        Feedback::factory()
            ->count(rand(2, 5))
            ->audioRegion()
            ->forPage($page)
            ->create([
                'creator_id' => $users->random()->id,
                'content' => fake()->randomElement([
                    'Audio quality is poor in this section.',
                    'Background noise is too loud here.',
                    'Speaker is unclear during this time range.',
                    'Volume needs to be adjusted.',
                    'Consider adding subtitles for this part.',
                ]),
            ]);

        // Create some video region feedbacks
        Feedback::factory()
            ->count(rand(1, 3))
            ->videoRegion()
            ->forPage($page)
            ->create([
                'creator_id' => $users->random()->id,
                'content' => fake()->randomElement([
                    'Lighting needs adjustment in this area.',
                    'Color correction required for this region.',
                    'Subject is out of focus during this time.',
                    'Consider cropping this area out.',
                    'Graphics overlay needed here.',
                ]),
            ]);

        // Create some video frame feedbacks
        Feedback::factory()
            ->count(rand(1, 4))
            ->videoFrame()
            ->forPage($page)
            ->create([
                'creator_id' => $users->random()->id,
                'content' => fake()->randomElement([
                    'This frame has a visual artifact.',
                    'Timestamp overlay is incorrect.',
                    'Frame transition is too abrupt.',
                    'Consider using this as a thumbnail.',
                    'Quality degradation visible in this frame.',
                ]),
            ]);
    }
}
