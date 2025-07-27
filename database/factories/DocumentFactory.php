<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
final class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(rand(2, 6)),
            'blocks' => $this->generateEditorJSBlocks(),
        ];
    }

    private function generateEditorJSBlocks(): array
    {
        $blocks = [
            [
                'id' => fake()->uuid(),
                'type' => 'header',
                'data' => [
                    'text' => fake()->sentence(rand(3, 8)),
                    'level' => rand(2, 4), // Match EditorJS config: levels 2, 3, 4
                ],
            ],
        ];

        $blockCount = rand(2, 4);
        for ($i = 0; $i < $blockCount; $i++) {
            $blocks[] = $this->generateRandomBlock();
        }

        return [
            'time' => now()->timestamp,
            'blocks' => $blocks,
            'version' => '2.28.2',
        ];
    }

    private function generateRandomBlock(): array
    {
        // Only use blocks that are actually loaded in EditorJS
        $blockTypes = ['header', 'table', 'paragraph'];
        $type = fake()->randomElement($blockTypes);

        return match ($type) {
            'header' => [
                'id' => fake()->uuid(),
                'type' => 'header',
                'data' => [
                    'text' => fake()->sentence(rand(4, 8)),
                    'level' => rand(2, 4), // Match EditorJS config
                ],
            ],
            'paragraph' => [
                'id' => fake()->uuid(),
                'type' => 'paragraph',
                'data' => [
                    'text' => fake()->paragraph(rand(2, 5)),
                ],
            ],
            'table' => [
                'id' => fake()->uuid(),
                'type' => 'table',
                'data' => [
                    'withHeadings' => fake()->boolean(70),
                    'content' => $this->generateTableContent(),
                ],
            ],
        };
    }

    private function generateTableContent(): array
    {
        $rows = rand(2, 4);
        $cols = rand(2, 4);
        $content = [];

        for ($row = 0; $row < $rows; $row++) {
            $rowData = [];
            for ($col = 0; $col < $cols; $col++) {
                $rowData[] = $row === 0
                    ? fake()->word() // Headers
                    : fake()->words(rand(1, 3), true); // Content
            }
            $content[] = $rowData;
        }

        return $content;
    }
}
