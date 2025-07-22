<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
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
                    'level' => rand(1, 3),
                ],
            ],
        ];

        $blockCount = rand(2, 8);
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
        $blockTypes = ['paragraph', 'list', 'quote', 'delimiter'];
        $type = fake()->randomElement($blockTypes);

        return match ($type) {
            'paragraph' => [
                'id' => fake()->uuid(),
                'type' => 'paragraph',
                'data' => [
                    'text' => fake()->paragraph(rand(2, 5)),
                ],
            ],
            'list' => [
                'id' => fake()->uuid(),
                'type' => 'list',
                'data' => [
                    'style' => fake()->randomElement(['ordered', 'unordered']),
                    'items' => fake()->sentences(rand(2, 5)),
                ],
            ],
            'quote' => [
                'id' => fake()->uuid(),
                'type' => 'quote',
                'data' => [
                    'text' => fake()->sentence(rand(8, 15)),
                    'caption' => fake()->name(),
                    'alignment' => fake()->randomElement(['left', 'center']),
                ],
            ],
            'delimiter' => [
                'id' => fake()->uuid(),
                'type' => 'delimiter',
                'data' => [],
            ],
        };
    }
}
