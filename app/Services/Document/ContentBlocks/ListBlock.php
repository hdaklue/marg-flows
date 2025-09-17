<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

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
                'lower-alpha' => $num <= 26 ? strtolower(chr(96 + $num)) : (string) $num,
                'upper-alpha' => $num <= 26 ? strtoupper(chr(64 + $num)) : (string) $num,
                'lower-roman' => $num <= 3999 ? strtolower(self::toRoman($num)) : (string) $num,
                'upper-roman' => $num <= 3999 ? strtoupper(self::toRoman($num)) : (string) $num,
                default => $num,
            };
            $formattedParts[] = (string) $part;
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
            'meta.counterType' => ['nullable', 'string', Rule::in(['numeric', 'lower-alpha', 'upper-alpha', 'lower-roman', 'upper-roman'])],
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

        return $this->renderNestedList($items, $style, [], $counterType, 0);
    }

    protected function renderNestedList(
        array $items,
        string $style = 'unordered',
        array $prefix = [],
        ?string $counterType = null,
        int $depth = 0,
    ): string {
        if ($depth >= 5) {
            return '';
        }
        $tag = $style === 'ordered' ? 'ol' : 'ul';
        $cssClass = $tag === 'ol' ? 'list-decimal list-inside' : 'list-disc list-inside';

        $htmlParts = ["<{$tag} class=\"{$cssClass}\">"];

        foreach ($items as $index => $item) {
            $content = htmlspecialchars($item['content'] ?? '', ENT_QUOTES, 'UTF-8');
            $nested = $item['items'] ?? [];
            $currentPrefix = [...$prefix, $index + 1];

            if ($style === 'ordered') {
                $prefixString = self::formatCounter($currentPrefix, $counterType ?? 'numeric') . '. ';
                $htmlParts[] = '<li><span class="mr-1">' . htmlspecialchars($prefixString, ENT_QUOTES, 'UTF-8') . '</span>' . $content;
            } else {
                $htmlParts[] = '<li>' . $content;
            }

            if (! empty($nested)) {
                $htmlParts[] = $this->renderNestedList($nested, $style, $currentPrefix, $counterType, $depth + 1);
            }

            $htmlParts[] = '</li>';
        }

        $htmlParts[] = "</{$tag}>";

        return implode('', $htmlParts);
    }
}
