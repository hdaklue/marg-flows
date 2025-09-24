<?php

declare(strict_types=1);

namespace Database\Seeders\BusinessDB;

use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use App\Models\Document;
use App\Models\User;
use App\Models\VideoFeedback;
use Illuminate\Database\Seeder;

final class VideoFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating video feedback in business database...');

        // Get test user from main database
        $testUser = User::where('email', 'test@example.com')->first();
        if (!$testUser) {
            $this->command->warn('Test user not found. Please run main DatabaseSeeder first.');

            return;
        }

        // Get documents from business database to associate feedback with
        $documents = Document::limit(5)->get();
        if ($documents->isEmpty()) {
            $this->command->warn(
                'No documents found in business database. Please run DocumentSeeder first.',
            );

            return;
        }

        $statuses = [
            FeedbackStatus::OPEN,
            FeedbackStatus::IN_PROGRESS,
            FeedbackStatus::RESOLVED,
            FeedbackStatus::URGENT,
        ];

        $urgencies = [
            FeedbackUrgency::LOW,
            FeedbackUrgency::NORMAL,
            FeedbackUrgency::HIGH,
            FeedbackUrgency::URGENT,
        ];

        // Create frame-based video feedback
        foreach ($documents as $document) {
            // Create 3-5 frame comments per document
            $frameCount = rand(3, 5);

            for ($i = 1; $i <= $frameCount; $i++) {
                $timestamp = rand(10, 300) + (rand(0, 999) / 1000); // 10s to 5min with milliseconds

                VideoFeedback::create([
                    'creator_id' => $testUser->id,
                    'content' => $this->generateFrameComment(),
                    'feedbackable_type' => $document->getMorphClass(),
                    'feedbackable_id' => $document->id,
                    'status' => fake()->randomElement($statuses),
                    'urgency' => fake()->randomElement($urgencies),
                    'feedback_type' => 'frame',
                    'timestamp' => $timestamp,
                    'x_coordinate' => rand(50, 1920 - 50), // Assuming 1920px width
                    'y_coordinate' => rand(50, 1080 - 50), // Assuming 1080px height
                    'region_data' => null,
                ]);
            }

            // Create 2-3 region comments per document
            $regionCount = rand(2, 3);

            for ($i = 1; $i <= $regionCount; $i++) {
                $startTime = rand(5, 240) + (rand(0, 999) / 1000); // 5s to 4min
                $duration = rand(5, 30) + (rand(0, 999) / 1000); // 5s to 30s duration
                $endTime = $startTime + $duration;

                VideoFeedback::create([
                    'creator_id' => $testUser->id,
                    'content' => $this->generateRegionComment(),
                    'feedbackable_type' => $document->getMorphClass(),
                    'feedbackable_id' => $document->id,
                    'status' => fake()->randomElement($statuses),
                    'urgency' => fake()->randomElement($urgencies),
                    'feedback_type' => 'region',
                    'timestamp' => null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'x_coordinate' => fake()->boolean(70) ? rand(100, 1820) : null, // 70% chance of having coordinates
                    'y_coordinate' => fake()->boolean(70) ? rand(100, 980) : null,
                    'region_data' => $this->generateRegionData(),
                ]);
            }
        }

        // Create some additional varied feedback for testing edge cases
        $this->createEdgeCaseFeedback($testUser, $documents->first());

        $totalVideoFeedback = VideoFeedback::count();
        $this->command->info(
            "✅ Created {$totalVideoFeedback} video feedback entries in business database",
        );
    }

    private function generateFrameComment(): string
    {
        $comments = [
            'This frame needs better lighting - too dark to see the details clearly.',
            'The composition here is excellent, but the subject appears slightly out of focus.',
            'Color grading looks off in this frame - too much saturation.',
            'Perfect timing for this shot, but could use some color correction.',
            'The framing is good but there\'s unwanted noise in the background.',
            'This moment captures the emotion perfectly - great work!',
            'Consider cropping this frame to focus more on the main subject.',
            'The exposure is too high here, losing detail in the highlights.',
            'Nice shot, but the audio sync seems off at this timestamp.',
            'This transition feels abrupt - maybe add a fade effect?',
            'Great visual storytelling in this frame.',
            'The camera shake is distracting here - stabilization needed.',
            'This frame would benefit from a wider shot to show more context.',
            'Perfect focus and depth of field in this shot.',
            'The typography overlay is hard to read against this background.',
        ];

        return fake()->randomElement($comments);
    }

    private function generateRegionComment(): string
    {
        $comments = [
            'This entire sequence needs pacing adjustments - it feels rushed.',
            'The audio levels are inconsistent throughout this region.',
            'Great storytelling flow in this section, very engaging.',
            'This segment could be shortened without losing impact.',
            'The music choice here doesn\'t match the mood of the scene.',
            'Excellent use of visual effects throughout this sequence.',
            'The dialogue in this section is hard to understand due to background noise.',
            'This transition sequence works really well with the overall narrative.',
            'Consider adding more dynamic shots in this region to maintain interest.',
            'The color temperature shifts too dramatically in this segment.',
            'This section effectively builds tension - well done.',
            'The pacing here is perfect for the emotional beats.',
            'Multiple shots in this region suffer from similar exposure issues.',
            'This sequence would benefit from tighter editing.',
            'The visual continuity is excellent throughout this region.',
        ];

        return fake()->randomElement($comments);
    }

    private function generateRegionData(): null|array
    {
        if (!fake()->boolean(60)) { // 60% chance of having region data
            return null;
        }

        return [
            'region_type' => fake()->randomElement([
                'rectangular',
                'circular',
                'freeform',
            ]),
            'bounds' => [
                'x' => rand(0, 1920),
                'y' => rand(0, 1080),
                'width' => rand(100, 800),
                'height' => rand(100, 600),
            ],
            'tracking' => fake()->boolean(30), // 30% chance of object tracking
            'effects' => fake()->randomElement([
                ['blur', 'tracking'],
                ['highlight', 'zoom'],
                ['crop', 'stabilize'],
                null,
            ]),
        ];
    }

    private function createEdgeCaseFeedback(User $user, Document $document): void
    {
        // Very short timestamp (beginning of video)
        VideoFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Opening frame needs adjustment - too abrupt.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::URGENT,
            'urgency' => FeedbackUrgency::HIGH,
            'feedback_type' => 'frame',
            'timestamp' => 0.5,
            'x_coordinate' => 100,
            'y_coordinate' => 100,
        ]);

        // Very long timestamp (near end of assumed long video)
        VideoFeedback::create([
            'creator_id' => $user->id,
            'content' => 'End credits timing could be extended.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::LOW,
            'feedback_type' => 'frame',
            'timestamp' => 3599.999, // Nearly 1 hour
            'x_coordinate' => 1800,
            'y_coordinate' => 1000,
        ]);

        // Very short region (quick cut)
        VideoFeedback::create([
            'creator_id' => $user->id,
            'content' => 'This quick cut is too jarring.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::IN_PROGRESS,
            'urgency' => FeedbackUrgency::NORMAL,
            'feedback_type' => 'region',
            'start_time' => 45.2,
            'end_time' => 45.8, // 0.6 second region
            'x_coordinate' => null,
            'y_coordinate' => null,
        ]);

        // Long region (extended sequence)
        VideoFeedback::create([
            'creator_id' => $user->id,
            'content' => 'This entire act has great pacing and flow.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::RESOLVED,
            'urgency' => FeedbackUrgency::LOW,
            'feedback_type' => 'region',
            'start_time' => 300.0,
            'end_time' => 1200.0, // 15 minute region
            'x_coordinate' => null,
            'y_coordinate' => null,
            'region_data' => [
                'region_type' => 'scene',
                'scene_name' => 'Act 2 - Character Development',
                'notes' => 'Excellent character development throughout this extended sequence.',
            ],
        ]);
    }
}
