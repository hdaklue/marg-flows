<?php

declare(strict_types=1);

namespace App\Services\Document\CustomBlocks;

use BumpCore\EditorPhp\Block\Block;
use Faker\Generator;
use Illuminate\Validation\Rule;

final class ListBlock extends Block
{
    public static function fake(Generator $faker): array
    {
        return [
            'style' => 'ordered',
            'meta' => ['counterType' => 'upper-alpha'],
            'items' => [
                [
                    'content' => $faker->sentence,
                    'meta' => [],
                    'items' => [
                        [
                            'content' => $faker->sentence,
                            'meta' => [],
                            'items' => [],
                        ],
                    ],
                ],
                [
                    'content' => $faker->sentence,
                    'meta' => [],
                    'items' => [],
                ],
            ],
        ];
    }

    protected static function formatCounter(array $prefix, string $type = 'numeric'): string
    {
        $formattedParts = [];
        foreach ($prefix as $num) {
            $part = match ($type) {
                'numeric' => $num,
                'lower-alpha' => strtolower(chr(96 + $num)), // a, b, c
                'upper-alpha' => strtoupper(chr(64 + $num)), // A, B, C
                'lower-roman' => strtolower(self::toRoman($num)), // Use self:: for static call
                'upper-roman' => strtoupper(self::toRoman($num)), // Use self:: for static call
                default => $num,
            };
            $formattedParts[] = (string) $part; // Ensure part is a string for implode
        }

        return implode('.', $formattedParts);
    }

    protected static function toRoman(int $number): string
    {
        if ($number < 1) { // Roman numerals are typically for positive integers
            return '';
        }
        $map = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        $result = '';
        foreach ($map as $roman => $value) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }

    public function allows(): array|string
    {
        return [
            'style' => [],
            'meta' => [],
            'items.*.content' => ['b', 'i', 'u', 'strong', 'em', 'span', 'a'],
            'items.*.meta' => [],
            'items.*.items' => [],
        ];
    }

    public function rules(): array
    {
        return [
            'style' => ['string', Rule::in(['ordered', 'unordered'])],
            'meta' => ['array'],
            'meta.counterType' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.content' => ['string'],
            'items.*.meta' => ['array'],
            'items.*.items' => ['array'],
        ];
    }

    public function render(): string
    {
        $items = $this->data->get('items') ?? [];
        $style = $this->data->get('style') ?? 'unordered';
        $meta = $this->data->get('meta', []); // Default to empty array for robustness
        $counterType = $style === 'ordered' ? $meta['counterType'] ?? 'numeric' : null;

        return $this->renderNestedList($items, $style, [], $counterType); // Pass $counterType
    }

    protected function renderNestedList(
        array $items,
        string $style = 'unordered',
        array $prefix = [],
        ?string $counterType = null,
    ): string {
        $tag = $style === 'ordered' ? 'ol' : 'ul';
        $cssClass = $tag === 'ol' ? 'list-inside list-none' : 'list-disc list-inside';

        $html = "<{$tag} class=\"{$cssClass}\">";

        foreach ($items as $index => $item) {
            // Assuming $item is always an array based on rules and fake data
            $content = $item['content'] ?? '';
            $nested = $item['items'] ?? [];
            $currentPrefix = array_merge($prefix, [$index + 1]);

            if ($style === 'ordered') {
                $prefixString =
                    self::formatCounter($currentPrefix, $counterType ?? 'numeric') . '. '; // Use self:: for static call
                $html .= '<li>' . "<span class=\"mr-1\">{$prefixString}</span>" . $content;
            } else {
                $html .= '<li>' . $content;
            }

            if (! empty($nested)) {
                $html .= $this->renderNestedList($nested, $style, $currentPrefix, $counterType);
            }

            $html .= '</li>';
        }

        $html .= "</{$tag}>";

        return $html;
    }
}
