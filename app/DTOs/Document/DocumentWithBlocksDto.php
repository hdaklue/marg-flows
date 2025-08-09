<?php

declare(strict_types=1);

namespace App\DTOs\Document;

use App\DTOs\EditorJS\EditorJSDocumentDto;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Carbon;
use WendellAdriel\ValidatedDTO\Concerns\Wireable;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * Document DTO with full blocks data for editing
 * 
 * @property string $id
 * @property string $name
 * @property EditorJSDocumentDto $blocks
 * @property User $creator
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property mixed $documentable
 */
final class DocumentWithBlocksDto extends ValidatedDTO
{
    use Wireable;

    protected function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'name' => ['required', 'string'],
            'blocks' => ['nullable'],
            'creator' => ['required'],
            'created_at' => ['required', 'date'],
            'updated_at' => ['required', 'date'],
            'documentable' => ['required'],
        ];
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'carbon',
            'updated_at' => 'carbon',
        ];
    }

    /**
     * Create DTO from Document model
     */
    public static function fromDocument(Document $document): self
    {
        // Handle nested blocks structure - extract the inner blocks if they exist
        $blocksData = null;
        if (is_array($document->blocks)) {
            // Check if this is a nested structure with blocks.blocks
            if (isset($document->blocks['blocks']) && is_array($document->blocks['blocks'])) {
                // Extract the inner EditorJS structure
                $blocksData = $document->blocks['blocks'];
            } else {
                // Use the blocks directly
                $blocksData = $document->blocks;
            }
        } else {
            $blocksData = $document->blocks ? json_decode($document->blocks, true) : null;
        }

        // Create EditorJS document DTO from blocks data
        $editorJSDocument = null;
        if ($blocksData) {
            try {
                $editorJSDocument = EditorJSDocumentDto::fromArray($blocksData);
            } catch (\Exception $e) {
                // Fallback to empty document if parsing fails
                \Log::warning('Failed to parse EditorJS blocks', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                    'blocks_data' => $blocksData
                ]);
                $editorJSDocument = EditorJSDocumentDto::createEmpty();
            }
        } else {
            $editorJSDocument = EditorJSDocumentDto::createEmpty();
        }

        return self::fromArray([
            'id' => $document->id,
            'name' => $document->name,
            'blocks' => $editorJSDocument,
            'creator' => $document->creator,
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
            'documentable' => $document->documentable,
        ]);
    }

    /**
     * Get blocks as JSON string for frontend consumption
     */
    public function getBlocksJson(): string
    {
        return $this->blocks?->getBlocksAsJson() ?? '{}';
    }

    /**
     * Get blocks as array for processing
     */
    public function getBlocksArray(): array
    {
        return $this->blocks?->getBlocksAsArray() ?? [];
    }

    /**
     * Check if document has any content
     */
    public function hasContent(): bool
    {
        return $this->blocks && $this->blocks->hasContent();
    }

    /**
     * Get blocks by type
     */
    public function getBlocksByType(string $type): array
    {
        return $this->blocks?->getBlocksByType($type)->toArray() ?? [];
    }

    /**
     * Check if document has blocks of specific type
     */
    public function hasBlockType(string $type): bool
    {
        return $this->blocks?->hasBlockType($type) ?? false;
    }

    /**
     * Update blocks from JSON string (for Livewire saves)
     */
    public function updateBlocksFromJson(string $json): void
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $this->blocks = EditorJSDocumentDto::fromArray($data);
        } catch (\JsonException $e) {
            \Log::error('Failed to parse blocks JSON', [
                'document_id' => $this->id,
                'error' => $e->getMessage(),
                'json' => $json
            ]);
            throw $e;
        }
    }

    /**
     * Convert blocks to database format (nested structure)
     */
    public function getBlocksForDatabase(): array
    {
        return [
            'time' => time(),
            'blocks' => $this->blocks?->toArray() ?? [],
            'version' => '2.28.2',
        ];
    }

    /**
     * Get updated at string for display
     */
    public function getUpdatedAtString(): string
    {
        return $this->updated_at->diffForHumans();
    }
}