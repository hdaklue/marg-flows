<?php

declare(strict_types=1);

namespace Database\Seeders\BusinessDB;

use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use App\Models\DesignFeedback;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DesignFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info(
            'Creating design feedback in business database...',
        );

        // Get test user from main database
        $testUser = User::where('email', 'test@example.com')->first();
        if (! $testUser) {
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

        $annotationTypes = [
            'point',
            'rectangle',
            'circle',
            'arrow',
            'text',
            'polygon',
            'area',
            'line',
            'freehand',
        ];

        $colors = [
            'red',
            'blue',
            'green',
            'yellow',
            'orange',
            'purple',
            'pink',
            'black',
            'white',
            'gray',
        ];

        // Create design feedback for each document
        foreach ($documents as $document) {
            // Create 8-12 design feedback entries per document
            $feedbackCount = rand(8, 12);

            for ($i = 1; $i <= $feedbackCount; $i++) {
                $annotationType = fake()->randomElement($annotationTypes);
                $x = rand(50, 1870); // Assuming 1920px canvas width
                $y = rand(50, 1030); // Assuming 1080px canvas height

                DesignFeedback::create([
                    'creator_id' => $testUser->id,
                    'content' => $this->generateDesignComment($annotationType),
                    'feedbackable_type' => $document->getMorphClass(),
                    'feedbackable_id' => $document->id,
                    'status' => fake()->randomElement($statuses),
                    'urgency' => fake()->randomElement($urgencies),
                    'x_coordinate' => $x,
                    'y_coordinate' => $y,
                    'annotation_type' => $annotationType,
                    'annotation_data' => $this->generateAnnotationData(
                        $annotationType,
                        $x,
                        $y,
                    ),
                    'area_bounds' => $this->generateAreaBounds($annotationType),
                    'color' => fake()->randomElement($colors),
                    'zoom_level' => fake()->randomFloat(2, 0.25, 3.0), // 25% to 300% zoom
                ]);
            }
        }

        // Create specialized design feedback for testing
        $this->createSpecializedDesignFeedback($testUser, $documents->first());

        $totalDesignFeedback = DesignFeedback::count();
        $this->command->info(
            "✅ Created {$totalDesignFeedback} design feedback entries in business database",
        );
    }

    private function generateDesignComment(string $annotationType): string
    {
        $comments = [
            'point' => [
                'This element needs attention - alignment is off.',
                'Perfect placement of this design element.',
                'Consider adjusting the color here.',
                'This spot needs better visual hierarchy.',
                'Good use of whitespace around this area.',
            ],
            'rectangle' => [
                'This section needs better contrast.',
                'The rectangular area works well for the layout.',
                'Consider adjusting the padding in this box.',
                'This content area could be more prominent.',
                'The border styling here is excellent.',
            ],
            'circle' => [
                'This circular element draws good attention.',
                'The radius could be adjusted for better proportion.',
                'Consider making this area more prominent.',
                'This circular selection highlights the issue well.',
                'The curved edges work perfectly here.',
            ],
            'arrow' => [
                'This flow direction makes sense.',
                'The arrow clearly indicates the user path.',
                'Consider changing the arrow style for consistency.',
                'This directional indicator is very helpful.',
                'The arrow placement guides the eye well.',
            ],
            'text' => [
                'Typography needs adjustment in this area.',
                'The text placement works well here.',
                'Consider increasing font size for readability.',
                'This text annotation clarifies the intent.',
                'Font weight could be adjusted for emphasis.',
            ],
            'area' => [
                'This entire area needs redesigning.',
                'The selected region has good visual balance.',
                'Consider restructuring this section layout.',
                'This area effectively uses the available space.',
                'The content organization here could be improved.',
            ],
            'line' => [
                'This line creates good visual separation.',
                'Consider adjusting line weight for consistency.',
                'The linear flow works well for the design.',
                'This divider helps organize the content.',
                'Line spacing could be more consistent.',
            ],
            'freehand' => [
                'This organic shape adds nice visual interest.',
                'The freehand annotation highlights the concern.',
                'Consider refining this custom shape.',
                'This hand-drawn element fits the brand style.',
                'The organic flow works well for this section.',
            ],
        ];

        $typeComments = $comments[$annotationType] ?? [
            'This annotation needs attention.',
            'The design element works well here.',
            'Consider making adjustments to this area.',
            'This selection demonstrates the point clearly.',
        ];

        return fake()->randomElement($typeComments);
    }

    private function generateAnnotationData(
        string $annotationType,
        int $x,
        int $y,
    ): ?array {
        if (! fake()->boolean(70)) { // 70% chance of having annotation data
            return null;
        }

        $baseData = [
            'created_at' => now()->toISOString(),
            'tool_version' => '2.1.0',
            'user_agent' => fake()->userAgent(),
        ];

        return match ($annotationType) {
            'arrow' => array_merge($baseData, [
                'startX' => $x,
                'startY' => $y,
                'endX' => $x + rand(-200, 200),
                'endY' => $y + rand(-200, 200),
                'arrowStyle' => fake()->randomElement([
                    'straight',
                    'curved',
                    'stepped',
                ]),
                'lineWidth' => rand(2, 8),
                'arrowHeadSize' => rand(8, 16),
            ]),
            'circle' => array_merge($baseData, [
                'centerX' => $x,
                'centerY' => $y,
                'radius' => rand(20, 100),
                'strokeWidth' => rand(2, 6),
                'filled' => fake()->boolean(30),
            ]),
            'rectangle' => array_merge($baseData, [
                'width' => rand(50, 300),
                'height' => rand(30, 200),
                'cornerRadius' => rand(0, 15),
                'strokeWidth' => rand(1, 5),
                'filled' => fake()->boolean(20),
            ]),
            'text' => array_merge($baseData, [
                'fontSize' => rand(12, 24),
                'fontFamily' => fake()->randomElement([
                    'Arial',
                    'Helvetica',
                    'Times',
                    'Georgia',
                ]),
                'fontWeight' => fake()->randomElement([
                    'normal',
                    'bold',
                    '600',
                ]),
                'textAlign' => fake()->randomElement([
                    'left',
                    'center',
                    'right',
                ]),
                'maxWidth' => rand(100, 300),
            ]),
            'line' => array_merge($baseData, [
                'startX' => $x,
                'startY' => $y,
                'endX' => $x + rand(-150, 150),
                'endY' => $y + rand(-150, 150),
                'lineStyle' => fake()->randomElement([
                    'solid',
                    'dashed',
                    'dotted',
                ]),
                'lineWidth' => rand(1, 6),
            ]),
            'freehand' => array_merge($baseData, [
                'points' => $this->generateFreehandPoints($x, $y),
                'smoothing' => fake()->randomFloat(2, 0.1, 1.0),
                'strokeWidth' => rand(2, 8),
            ]),
            default => $baseData,
        };
    }

    private function generateAreaBounds(string $annotationType): ?array
    {
        $areaTypes = ['rectangle', 'circle', 'polygon', 'area'];

        if (! in_array($annotationType, $areaTypes) || ! fake()->boolean(80)) {
            return null;
        }

        return match ($annotationType) {
            'rectangle', 'area' => [
                'width' => rand(100, 400),
                'height' => rand(80, 300),
                'rotation' => fake()->boolean(20) ? rand(-15, 15) : 0,
            ],
            'circle' => [
                'width' => rand(80, 200),
                'height' => rand(80, 200), // Same as width for circle
                'radius' => rand(40, 100),
            ],
            'polygon' => [
                'width' => rand(120, 350),
                'height' => rand(100, 280),
                'points' => rand(3, 8), // Number of polygon vertices
                'vertices' => $this->generatePolygonVertices(),
            ],
            default => null,
        };
    }

    private function generateFreehandPoints(int $centerX, int $centerY): array
    {
        $pointCount = rand(10, 30);
        $points = [];

        for ($i = 0; $i < $pointCount; $i++) {
            $angle = ($i / $pointCount) * 2 * pi();
            $radius = rand(20, 80);
            $noise = rand(-10, 10);

            $points[] = [
                'x' => $centerX + (($radius + $noise) * cos($angle)),
                'y' => $centerY + (($radius + $noise) * sin($angle)),
                'pressure' => fake()->randomFloat(2, 0.3, 1.0),
            ];
        }

        return $points;
    }

    private function generatePolygonVertices(): array
    {
        $vertexCount = rand(3, 8);
        $vertices = [];

        for ($i = 0; $i < $vertexCount; $i++) {
            $vertices[] = [
                'x' => rand(-100, 100),
                'y' => rand(-100, 100),
            ];
        }

        return $vertices;
    }

    private function createSpecializedDesignFeedback(
        User $user,
        Document $document,
    ): void {
        // High-precision point annotation
        DesignFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Pixel-perfect alignment issue - 1px offset detected.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::URGENT,
            'urgency' => FeedbackUrgency::HIGH,
            'x_coordinate' => 960, // Center of 1920px
            'y_coordinate' => 540, // Center of 1080px
            'annotation_type' => 'point',
            'color' => 'red',
            'zoom_level' => 4.0, // High zoom for precision
            'annotation_data' => [
                'precision' => 'pixel-perfect',
                'measurement' => '1px offset',
                'expected_x' => 961,
                'actual_x' => 960,
            ],
        ]);

        // Large area selection
        DesignFeedback::create([
            'creator_id' => $user->id,
            'content' => 'This entire section needs complete redesign - layout is not working.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::HIGH,
            'x_coordinate' => 200,
            'y_coordinate' => 150,
            'annotation_type' => 'rectangle',
            'color' => 'orange',
            'zoom_level' => 0.5, // Zoomed out to see full area
            'area_bounds' => [
                'width' => 800,
                'height' => 600,
                'rotation' => 0,
            ],
            'annotation_data' => [
                'severity' => 'major_redesign',
                'affected_elements' => [
                    'header',
                    'navigation',
                    'content_area',
                    'sidebar',
                ],
                'priority' => 'high',
            ],
        ]);

        // Complex arrow annotation showing user flow
        DesignFeedback::create([
            'creator_id' => $user->id,
            'content' => 'User flow is confusing here - should direct to checkout instead.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::IN_PROGRESS,
            'urgency' => FeedbackUrgency::NORMAL,
            'x_coordinate' => 400,
            'y_coordinate' => 300,
            'annotation_type' => 'arrow',
            'color' => 'blue',
            'zoom_level' => 1.0,
            'annotation_data' => [
                'startX' => 400,
                'startY' => 300,
                'endX' => 600,
                'endY' => 200,
                'arrowStyle' => 'curved',
                'lineWidth' => 4,
                'arrowHeadSize' => 12,
                'flow_type' => 'user_journey',
                'current_action' => 'add_to_cart',
                'suggested_action' => 'proceed_to_checkout',
            ],
        ]);

        // Multiple overlapping annotations (cluster)
        $baseX = 800;
        $baseY = 400;

        for ($i = 0; $i < 5; $i++) {
            DesignFeedback::create([
                'creator_id' => $user->id,
                'content' => 'Multiple issues in this area - concern #' . ($i + 1) . '.',
                'feedbackable_type' => $document->getMorphClass(),
                'feedbackable_id' => $document->id,
                'status' => fake()->randomElement([
                    FeedbackStatus::OPEN,
                    FeedbackStatus::IN_PROGRESS,
                ]),
                'urgency' => fake()->randomElement([
                    FeedbackUrgency::NORMAL,
                    FeedbackUrgency::HIGH,
                ]),
                'x_coordinate' => $baseX + rand(-30, 30),
                'y_coordinate' => $baseY + rand(-30, 30),
                'annotation_type' => 'point',
                'color' => fake()->randomElement(['red', 'orange', 'yellow']),
                'zoom_level' => 1.5,
                'annotation_data' => [
                    'cluster_id' => 'cluster_1',
                    'issue_type' => fake()->randomElement([
                        'spacing',
                        'alignment',
                        'color',
                        'typography',
                        'usability',
                    ]),
                    'related_annotations' => 4, // Total in cluster
                ],
            ]);
        }

        // Text annotation with formatting
        DesignFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Typography hierarchy needs improvement throughout this section.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::NORMAL,
            'x_coordinate' => 300,
            'y_coordinate' => 600,
            'annotation_type' => 'text',
            'color' => 'black',
            'zoom_level' => 1.2,
            'annotation_data' => [
                'fontSize' => 16,
                'fontFamily' => 'Arial',
                'fontWeight' => 'bold',
                'textAlign' => 'left',
                'maxWidth' => 250,
                'background_color' => 'rgba(255, 255, 255, 0.9)',
                'border' => '1px solid #ccc',
            ],
        ]);

        // Edge case: annotation at canvas boundary
        DesignFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Content is getting cut off at the edge - needs margin adjustment.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::URGENT,
            'urgency' => FeedbackUrgency::URGENT,
            'x_coordinate' => 1900, // Near right edge
            'y_coordinate' => 50, // Near top edge
            'annotation_type' => 'point',
            'color' => 'red',
            'zoom_level' => 2.0,
            'annotation_data' => [
                'edge_case' => true,
                'boundary_type' => 'canvas_edge',
                'clipping_detected' => true,
            ],
        ]);
    }
}
