<?php

declare(strict_types=1);

namespace Database\Seeders\BusinessDB;

use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DocumentFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info(
            'Creating document feedback in business database...',
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

        $elementTypes = [
            'paragraph',
            'header',
            'list',
            'quote',
            'code',
            'table',
            'image',
            'embed',
            'delimiter',
            'warning',
            'checklist',
        ];

        // Create document feedback for each document
        foreach ($documents as $document) {
            // Create 6-10 feedback entries per document
            $feedbackCount = rand(6, 10);

            for ($i = 1; $i <= $feedbackCount; $i++) {
                $elementType = fake()->randomElement($elementTypes);
                $blockId = $this->generateBlockId();
                $hasTextSelection = fake()->boolean(40); // 40% chance of text selection

                DocumentFeedback::create([
                    'creator_id' => $testUser->id,
                    'content' => $this->generateDocumentComment(
                        $elementType,
                        $hasTextSelection,
                    ),
                    'feedbackable_type' => $document->getMorphClass(),
                    'feedbackable_id' => $document->id,
                    'status' => fake()->randomElement($statuses),
                    'urgency' => fake()->randomElement($urgencies),
                    'block_id' => $blockId,
                    'element_type' => $elementType,
                    'position_data' => $this->generatePositionData($elementType),
                    'block_version' => $this->generateBlockVersion(),
                    'selection_data' => $hasTextSelection
                        ? $this->generateSelectionData()
                        : null,
                ]);
            }
        }

        // Create specialized document feedback for testing
        $this->createSpecializedDocumentFeedback(
            $testUser,
            $documents->first(),
        );

        $totalDocumentFeedback = DocumentFeedback::count();
        $this->command->info(
            "✅ Created {$totalDocumentFeedback} document feedback entries in business database",
        );
    }

    private function generateBlockId(): string
    {
        // Generate realistic Editor.js block IDs
        return 'block_' . fake()->uuid();
    }

    private function generateBlockVersion(): ?string
    {
        if (! fake()->boolean(70)) { // 70% chance of having version info
            return null;
        }

        return
            'v'
            . fake()->numberBetween(1, 10)
            . '.'
            . fake()->numberBetween(0, 9)
            . '.'
            . fake()->sha1();
    }

    private function generateDocumentComment(
        string $elementType,
        bool $hasTextSelection,
    ): string {
        $blockComments = [
            'paragraph' => [
                'This paragraph could be restructured for better flow.',
                'Consider breaking this into smaller paragraphs for readability.',
                'The content here is excellent but needs minor grammar fixes.',
                'This section would benefit from more specific examples.',
            ],
            'header' => [
                'Header level should be adjusted for proper hierarchy.',
                'This heading is perfect for the section structure.',
                'Consider making this header more descriptive.',
                'The header formatting looks great here.',
            ],
            'list' => [
                'List items could be reordered for better logical flow.',
                'This list is comprehensive and well-organized.',
                'Consider using numbered list instead of bullets here.',
                'Some list items could be combined for clarity.',
            ],
            'quote' => [
                'The quote attribution needs to be added.',
                'This quote perfectly supports the argument.',
                'Consider using a different quote that\'s more relevant.',
                'Quote formatting looks professional.',
            ],
            'code' => [
                'Code syntax highlighting could be improved.',
                'This code example is clear and helpful.',
                'Consider adding comments to explain the code.',
                'Code block formatting needs adjustment.',
            ],
            'table' => [
                'Table data needs verification for accuracy.',
                'This table effectively presents the information.',
                'Consider adding headers to improve table readability.',
                'Table formatting could be improved for mobile view.',
            ],
            'image' => [
                'Image resolution could be higher for better quality.',
                'This image perfectly illustrates the concept.',
                'Consider adding alt text for accessibility.',
                'Image placement works well in this context.',
            ],
        ];

        $textSelectionComments = [
            'This specific text needs clarification.',
            'The selected phrase could be rephrased for clarity.',
            'This terminology should be defined for readers.',
            'The highlighted text contains a factual error.',
            'This sentence structure could be improved.',
            'The selected text is well-written and impactful.',
            'Consider replacing this phrase with something more precise.',
            'This text selection demonstrates the point perfectly.',
        ];

        if ($hasTextSelection) {
            return fake()->randomElement($textSelectionComments);
        }

        $elementComments = $blockComments[$elementType] ?? [
            'This block needs some attention.',
            'The content here is well-structured.',
            'Consider revising this section.',
            'This element works well in context.',
        ];

        return fake()->randomElement($elementComments);
    }

    private function generatePositionData(string $elementType): ?array
    {
        if (! fake()->boolean(60)) { // 60% chance of having position data
            return null;
        }

        $baseData = [
            'blockIndex' => fake()->numberBetween(0, 20),
            'blockType' => $elementType,
            'documentPosition' => fake()->numberBetween(0, 5000), // Character position in document
        ];

        // Add element-specific position data
        return match ($elementType) {
            'table' => array_merge($baseData, [
                'tableRow' => fake()->numberBetween(0, 10),
                'tableColumn' => fake()->numberBetween(0, 5),
                'cellType' => fake()->randomElement(['header', 'data']),
            ]),
            'list' => array_merge($baseData, [
                'listItemIndex' => fake()->numberBetween(0, 15),
                'listType' => fake()->randomElement(['ordered', 'unordered']),
                'nestingLevel' => fake()->numberBetween(0, 3),
            ]),
            'header' => array_merge($baseData, [
                'headerLevel' => fake()->numberBetween(1, 6),
                'sectionNumber' => fake()->numberBetween(1, 10),
            ]),
            'code' => array_merge($baseData, [
                'language' => fake()->randomElement([
                    'javascript',
                    'php',
                    'python',
                    'html',
                    'css',
                ]),
                'lineNumber' => fake()->numberBetween(1, 50),
            ]),
            default => $baseData,
        };
    }

    private function generateSelectionData(): array
    {
        $sampleTexts = [
            'This is an important concept',
            'The implementation details',
            'Furthermore, we should consider',
            'According to recent studies',
            'In conclusion',
            'However, it is worth noting',
            'The primary objective',
            'Subsequently',
            'Nevertheless',
            'Therefore, it follows that',
        ];

        $selectedText = fake()->randomElement($sampleTexts);
        $start = fake()->numberBetween(0, 500);
        $length = mb_strlen($selectedText);

        return [
            'selectedText' => $selectedText,
            'start' => $start,
            'end' => $start + $length,
            'length' => $length,
            'context' => [
                'before' => fake()->sentence(),
                'after' => fake()->sentence(),
            ],
            'selectionType' => fake()->randomElement([
                'word',
                'sentence',
                'phrase',
                'paragraph',
            ]),
        ];
    }

    private function createSpecializedDocumentFeedback(
        User $user,
        Document $document,
    ): void {
        // Header hierarchy feedback
        DocumentFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Header hierarchy is inconsistent - this should be H2, not H1.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::URGENT,
            'urgency' => FeedbackUrgency::HIGH,
            'block_id' => 'block_header_' . fake()->uuid(),
            'element_type' => 'header',
            'position_data' => [
                'blockIndex' => 5,
                'headerLevel' => 1,
                'expectedLevel' => 2,
                'sectionNumber' => 3,
            ],
        ]);

        // Long text selection feedback
        DocumentFeedback::create([
            'creator_id' => $user->id,
            'content' => 'This entire paragraph needs significant revision for clarity and flow.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::IN_PROGRESS,
            'urgency' => FeedbackUrgency::NORMAL,
            'block_id' => 'block_para_' . fake()->uuid(),
            'element_type' => 'paragraph',
            'selection_data' => [
                'selectedText' => 'This is a very long paragraph that contains multiple sentences and complex ideas that could be better organized into smaller, more digestible chunks for improved readability and user comprehension.',
                'start' => 0,
                'end' => 180,
                'length' => 180,
                'selectionType' => 'paragraph',
                'context' => [
                    'before' => '',
                    'after' => 'The following section discusses...',
                ],
            ],
        ]);

        // Code block with line-specific feedback
        DocumentFeedback::create([
            'creator_id' => $user->id,
            'content' => 'This function could be optimized and needs better error handling.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::HIGH,
            'block_id' => 'block_code_' . fake()->uuid(),
            'element_type' => 'code',
            'position_data' => [
                'blockIndex' => 12,
                'language' => 'php',
                'lineNumber' => 15,
                'functionName' => 'processUserData',
            ],
            'selection_data' => [
                'selectedText' => 'function processUserData($data) {
    return json_decode($data);
}',
                'start' => 0,
                'end' => 65,
                'length' => 65,
                'selectionType' => 'code_block',
            ],
        ]);

        // Table cell feedback
        DocumentFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Data in this cell appears to be incorrect - please verify.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::URGENT,
            'urgency' => FeedbackUrgency::URGENT,
            'block_id' => 'block_table_' . fake()->uuid(),
            'element_type' => 'table',
            'position_data' => [
                'blockIndex' => 8,
                'tableRow' => 3,
                'tableColumn' => 2,
                'cellType' => 'data',
                'cellValue' => '$1,234.56',
            ],
        ]);

        // List item feedback
        DocumentFeedback::create([
            'creator_id' => $user->id,
            'content' => 'This list item should be moved to position 2 for better logical flow.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::LOW,
            'block_id' => 'block_list_' . fake()->uuid(),
            'element_type' => 'list',
            'position_data' => [
                'blockIndex' => 15,
                'listItemIndex' => 4,
                'listType' => 'ordered',
                'nestingLevel' => 0,
                'suggestedPosition' => 1,
            ],
        ]);
    }
}
