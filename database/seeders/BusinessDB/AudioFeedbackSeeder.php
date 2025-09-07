<?php

declare(strict_types=1);

namespace Database\Seeders\BusinessDB;

use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use App\Models\AudioFeedback;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

final class AudioFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating audio feedback in business database...');

        // Get test user from main database
        $testUser = User::where('email', 'test@example.com')->first();
        if (!$testUser) {
            $this->command->warn(
                'Test user not found. Please run main DatabaseSeeder first.',
            );
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

        // Create audio feedback for each document
        foreach ($documents as $document) {
            // Create 4-6 audio feedback entries per document
            $feedbackCount = rand(4, 6);

            for ($i = 1; $i <= $feedbackCount; $i++) {
                $startTime = rand(5, 600) + (rand(0, 999) / 1000); // 5s to 10min
                $duration = $this->generateRealisticDuration();
                $endTime = $startTime + $duration;

                AudioFeedback::create([
                    'creator_id' => $testUser->id,
                    'content' => $this->generateAudioComment($duration),
                    'feedbackable_type' => $document->getMorphClass(),
                    'feedbackable_id' => $document->id,
                    'status' => fake()->randomElement($statuses),
                    'urgency' => fake()->randomElement($urgencies),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'waveform_data' => $this->generateWaveformData($duration),
                    'peak_amplitude' => $this->generatePeakAmplitude(),
                    'frequency_data' => $this->generateFrequencyData(),
                ]);
            }
        }

        // Create some specialized audio feedback for testing
        $this->createSpecializedAudioFeedback($testUser, $documents->first());

        $totalAudioFeedback = AudioFeedback::count();
        $this->command->info(
            "✅ Created {$totalAudioFeedback} audio feedback entries in business database",
        );
    }

    private function generateRealisticDuration(): float
    {
        // Generate realistic audio clip durations
        $type = rand(1, 100);

        return match (true) {
            $type <= 20 => rand(1, 5) + (rand(0, 999) / 1000), // Short clips (1-5s) - 20%
            $type <= 50 => rand(5, 15) + (rand(0, 999) / 1000), // Medium clips (5-15s) - 30%
            $type <= 80 => rand(15, 60) + (rand(0, 999) / 1000), // Long clips (15-60s) - 30%
            default => rand(60, 300) + (rand(0, 999) / 1000), // Very long clips (1-5min) - 20%
        };
    }

    private function generateAudioComment(float $duration): string
    {
        $shortClipComments = [
            'Audio level spike needs correction here.',
            'Background noise interference in this section.',
            'Perfect voice clarity in this clip.',
            'Echo effect is too pronounced.',
            'Good audio balance between voice and music.',
            'Microphone popping sound needs removal.',
            'Ambient sound adds nice atmosphere.',
        ];

        $mediumClipComments = [
            'The audio mix in this segment could be improved.',
            'Great vocal performance throughout this section.',
            'Background music overpowers the dialogue here.',
            'Audio compression artifacts are noticeable.',
            'The reverb settings work well for this scene.',
            'Some frequency ranges need EQ adjustment.',
            'Overall audio quality is excellent in this clip.',
        ];

        $longClipComments = [
            'This entire conversation has inconsistent audio levels.',
            'The music transition in this section is seamless.',
            'Multiple audio issues throughout this extended clip.',
            'Excellent sound design and mixing throughout.',
            'The ambient soundscape really enhances the mood.',
            'Several dialogue sections need noise reduction.',
            'The audio narrative flow is compelling here.',
        ];

        return match (true) {
            $duration <= 5 => fake()->randomElement($shortClipComments),
            $duration <= 60 => fake()->randomElement($mediumClipComments),
            default => fake()->randomElement($longClipComments),
        };
    }

    private function generateWaveformData(float $duration): null|array
    {
        if (!fake()->boolean(70)) { // 70% chance of having waveform data
            return null;
        }

        // Generate realistic waveform points based on duration
        $sampleRate = 10; // 10 samples per second for visualization
        $totalSamples = (int) ceil($duration * $sampleRate);
        $waveform = [];

        for ($i = 0; $i < $totalSamples; $i++) {
            // Generate somewhat realistic waveform with varying amplitudes
            $baseAmplitude = fake()->randomFloat(3, 0.1, 0.9);
            $noise = fake()->randomFloat(3, -0.1, 0.1);
            $amplitude = max(0, min(1, $baseAmplitude + $noise));

            $waveform[] = [
                'time' => round($i / $sampleRate, 3),
                'amplitude' => $amplitude,
            ];
        }

        return [
            'sample_rate' => $sampleRate,
            'total_samples' => $totalSamples,
            'duration' => $duration,
            'points' => $waveform,
        ];
    }

    private function generatePeakAmplitude(): null|float
    {
        if (!fake()->boolean(80)) { // 80% chance of having peak amplitude data
            return null;
        }

        // Generate realistic peak amplitude values
        $type = rand(1, 100);

        return match (true) {
            $type <= 10 => fake()->randomFloat(4, 0.0, 0.2), // Very quiet - 10%
            $type <= 30 => fake()->randomFloat(4, 0.2, 0.4), // Quiet - 20%
            $type <= 70 => fake()->randomFloat(4, 0.4, 0.7), // Normal - 40%
            $type <= 90 => fake()->randomFloat(4, 0.7, 0.9), // Loud - 20%
            default => fake()->randomFloat(4, 0.9, 1.0), // Very loud - 10%
        };
    }

    private function generateFrequencyData(): null|array
    {
        if (!fake()->boolean(50)) { // 50% chance of having frequency data
            return null;
        }

        // Generate frequency analysis data
        $frequencies = [
            'bass' => fake()->randomFloat(3, 0.0, 1.0), // 20-250 Hz
            'low_mid' => fake()->randomFloat(3, 0.0, 1.0), // 250-500 Hz
            'mid' => fake()->randomFloat(3, 0.0, 1.0), // 500-2000 Hz
            'high_mid' => fake()->randomFloat(3, 0.0, 1.0), // 2000-4000 Hz
            'treble' => fake()->randomFloat(3, 0.0, 1.0), // 4000+ Hz
        ];

        return [
            'analysis_type' => 'frequency_spectrum',
            'frequency_bands' => $frequencies,
            'dominant_frequency' => fake()->randomElement([
                'bass',
                'mid',
                'high_mid',
            ]),
            'spectral_centroid' => fake()->randomFloat(1, 500, 3000),
            'notes' => fake()->randomElement([
                'Rich bass content',
                'Balanced frequency response',
                'High-frequency emphasis',
                'Mid-range focused',
                null,
            ]),
        ];
    }

    private function createSpecializedAudioFeedback(
        User $user,
        Document $document,
    ): void {
        // Very short audio clip (sound effect or quick transition)
        AudioFeedback::create([
            'creator_id' => $user->id,
            'content' => 'This sound effect timing is perfect.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::RESOLVED,
            'urgency' => FeedbackUrgency::LOW,
            'start_time' => 12.3,
            'end_time' => 12.5, // 0.2 second clip
            'peak_amplitude' => 0.95,
            'waveform_data' => null,
            'frequency_data' => [
                'analysis_type' => 'transient',
                'frequency_bands' => [
                    'bass' => 0.1,
                    'mid' => 0.3,
                    'high_mid' => 0.8,
                    'treble' => 0.9,
                ],
                'dominant_frequency' => 'treble',
                'notes' => 'Sharp transient sound',
            ],
        ]);

        // Very long audio segment (background music or ambient sound)
        AudioFeedback::create([
            'creator_id' => $user->id,
            'content' => 'The background music throughout this section creates perfect ambiance.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::NORMAL,
            'start_time' => 60.0,
            'end_time' => 420.0, // 6 minute segment
            'peak_amplitude' => 0.35,
            'waveform_data' => [
                'sample_rate' => 2, // Lower sample rate for long clips
                'duration' => 360.0,
                'notes' => 'Consistent ambient background',
            ],
            'frequency_data' => [
                'analysis_type' => 'ambient',
                'frequency_bands' => [
                    'bass' => 0.6,
                    'low_mid' => 0.4,
                    'mid' => 0.3,
                    'high_mid' => 0.2,
                    'treble' => 0.1,
                ],
                'dominant_frequency' => 'bass',
                'notes' => 'Warm, low-frequency ambient sound',
            ],
        ]);

        // High amplitude audio (needs attention)
        AudioFeedback::create([
            'creator_id' => $user->id,
            'content' => 'URGENT: Audio clipping detected - levels too high!',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::URGENT,
            'urgency' => FeedbackUrgency::URGENT,
            'start_time' => 145.2,
            'end_time' => 152.8,
            'peak_amplitude' => 1.0, // Clipping
            'frequency_data' => [
                'analysis_type' => 'clipping_detected',
                'notes' => 'Audio levels exceed maximum threshold',
            ],
        ]);

        // Very quiet audio (needs boost)
        AudioFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Audio levels too low - dialogue barely audible.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::IN_PROGRESS,
            'urgency' => FeedbackUrgency::HIGH,
            'start_time' => 234.5,
            'end_time' => 248.2,
            'peak_amplitude' => 0.08, // Very quiet
            'frequency_data' => [
                'analysis_type' => 'low_level',
                'notes' => 'Requires significant amplification',
            ],
        ]);
    }
}
