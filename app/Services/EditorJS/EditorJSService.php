<?php

declare(strict_types=1);

namespace App\Services\EditorJS;

use App\ValueObjects\EditorJS\EditorJSBlock;
use App\ValueObjects\EditorJS\EditorJSBlockCollection;
use App\ValueObjects\EditorJS\EditorJSData;

/**
 * Service for advanced EditorJS operations
 * Handles document analysis, validation, and transformation
 */
class EditorJSService
{
    /**
     * Extract plain text from EditorJS document
     */
    public function extractPlainText(EditorJSData $data): string
    {
        $textParts = [];

        foreach ($data->getBlocks() as $block) {
            $text = $this->extractTextFromBlock($block);
            if ($text !== '') {
                $textParts[] = $text;
            }
        }

        return implode("\n\n", $textParts);
    }

    /**
     * Get document word count
     */
    public function getWordCount(EditorJSData $data): int
    {
        $text = $this->extractPlainText($data);
        return str_word_count($text);
    }

    /**
     * Get document character count
     */
    public function getCharacterCount(EditorJSData $data): int
    {
        $text = $this->extractPlainText($data);
        return mb_strlen($text);
    }

    /**
     * Get blocks count by type
     */
    public function getBlockStatistics(EditorJSData $data): array
    {
        $stats = [];
        $blocks = $data->getBlocks();

        foreach ($blocks->getBlockTypes() as $type) {
            $stats[$type] = count($blocks->getBlocksByType($type));
        }

        return [
            'total_blocks' => $blocks->count(),
            'by_type' => $stats,
            'word_count' => $this->getWordCount($data),
            'character_count' => $this->getCharacterCount($data),
        ];
    }

    /**
     * Validate document structure
     */
    public function validate(EditorJSData $data): array
    {
        $errors = [];
        $config = config('editor');

        // Check total blocks limit
        if ($data->getBlocks()->count() > ($config['validation']['max_blocks'] ?? 1000)) {
            $errors[] = 'Document exceeds maximum block count';
        }

        // Check allowed block types
        $allowedTypes = $config['allowed_blocks'] ?? [];
        if (!empty($allowedTypes)) {
            foreach ($data->getBlocks() as $block) {
                if (!in_array($block->getType(), $allowedTypes)) {
                    $errors[] = "Block type '{$block->getType()}' is not allowed";
                }
            }
        }

        // Check text length limits
        $maxTextLength = $config['validation']['max_text_length'] ?? 10000;
        $totalTextLength = $this->getCharacterCount($data);
        if ($totalTextLength > $maxTextLength) {
            $errors[] = 'Document text exceeds maximum character limit';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Clean empty blocks from document
     */
    public function cleanEmptyBlocks(EditorJSData $data): EditorJSData
    {
        $cleanedBlocks = $data->getBlocks()->filter(
            fn(EditorJSBlock $block) => !$block->isEmpty()
        );

        return $data->withBlocks($cleanedBlocks->toArray());
    }

    /**
     * Convert document to different format
     */
    public function convertToMarkdown(EditorJSData $data): string
    {
        $markdown = [];

        foreach ($data->getBlocks() as $block) {
            $markdown[] = $this->convertBlockToMarkdown($block);
        }

        return implode("\n\n", array_filter($markdown));
    }

    /**
     * Search for text within document blocks
     */
    public function searchText(EditorJSData $data, string $query, bool $caseSensitive = false): array
    {
        $results = [];
        $flags = $caseSensitive ? 0 : PREG_CASELESS;

        foreach ($data->getBlocks() as $index => $block) {
            $text = $this->extractTextFromBlock($block);
            
            if (preg_match("/{$query}/u{$flags}", $text)) {
                $results[] = [
                    'block_index' => $index,
                    'block_type' => $block->getType(),
                    'block_id' => $block->getId(),
                    'text' => $text,
                ];
            }
        }

        return $results;
    }

    private function extractTextFromBlock(EditorJSBlock $block): string
    {
        $data = $block->getData();

        return match ($block->getType()) {
            'paragraph', 'header' => $data['text'] ?? '',
            'quote' => ($data['text'] ?? '') . ' — ' . ($data['caption'] ?? ''),
            'list' => implode(', ', $data['items'] ?? []),
            'checklist' => implode(', ', array_column($data['items'] ?? [], 'text')),
            'code' => $data['code'] ?? '',
            'table' => $this->extractTableText($data['content'] ?? []),
            default => '',
        };
    }

    private function extractTableText(array $tableData): string
    {
        $text = [];
        foreach ($tableData as $row) {
            if (is_array($row)) {
                $text[] = implode(' | ', $row);
            }
        }
        return implode("\n", $text);
    }

    private function convertBlockToMarkdown(EditorJSBlock $block): string
    {
        $data = $block->getData();

        return match ($block->getType()) {
            'header' => str_repeat('#', $data['level'] ?? 1) . ' ' . ($data['text'] ?? ''),
            'paragraph' => $data['text'] ?? '',
            'quote' => '> ' . ($data['text'] ?? '') . 
                       (isset($data['caption']) ? "\n> \n> — " . $data['caption'] : ''),
            'list' => $this->convertListToMarkdown($data),
            'checklist' => $this->convertChecklistToMarkdown($data),
            'code' => "```\n" . ($data['code'] ?? '') . "\n```",
            'delimiter' => '---',
            default => '',
        };
    }

    private function convertListToMarkdown(array $data): string
    {
        $items = $data['items'] ?? [];
        $style = $data['style'] ?? 'unordered';
        $prefix = $style === 'ordered' ? '1. ' : '- ';

        return implode("\n", array_map(
            fn($item) => $prefix . $item,
            $items
        ));
    }

    private function convertChecklistToMarkdown(array $data): string
    {
        $items = $data['items'] ?? [];

        return implode("\n", array_map(
            fn($item) => '- [' . ($item['checked'] ? 'x' : ' ') . '] ' . $item['text'],
            $items
        ));
    }
}