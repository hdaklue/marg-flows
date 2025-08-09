<?php

declare(strict_types=1);

namespace App\Casts;

use App\DTOs\EditorJS\EditorJSDocumentDto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Custom cast for EditorJS blocks using DTOs
 * Handles conversion between database JSON and EditorJS DTOs
 */
class EditorJSBlocksCast implements CastsAttributes
{
    /**
     * Cast the given value from the database
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EditorJSDocumentDto
    {
        if ($value === null) {
            return null;
        }

        // Handle empty strings or empty arrays
        if ($value === '' || $value === '[]' || $value === '{}') {
            return EditorJSDocumentDto::createWithBlocks([]);
        }

        try {
            // Parse JSON if it's a string
            $data = is_string($value) ? json_decode($value, true) : $value;
            
            if (!is_array($data)) {
                throw new InvalidArgumentException('EditorJS data must be an array or valid JSON');
            }

            // Handle the complex nested structure from the database
            return EditorJSDocumentDto::fromNestedArray($data);
            
        } catch (\JsonException $e) {
            throw new InvalidArgumentException("Invalid JSON for EditorJS data: {$e->getMessage()}");
        } catch (\Exception $e) {
            // Log the error but return empty data instead of failing
            logger()->warning('Failed to parse EditorJS data', [
                'model' => get_class($model),
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            
            return EditorJSDocumentDto::createWithBlocks([]);
        }
    }

    /**
     * Prepare the given value for storage in the database
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        // Handle EditorJSDocumentDto instances
        if ($value instanceof EditorJSDocumentDto) {
            return [$key => json_encode($this->formatForDatabase($value))];
        }

        // Handle arrays - assume it's blocks data
        if (is_array($value)) {
            // If it's already in the correct nested format, store as-is
            if (isset($value['blocks'], $value['time'], $value['version'])) {
                return [$key => json_encode($value)];
            }
            
            // If it's in the nested blocks format (with internal blocks structure)
            if (isset($value['blocks']) && is_array($value['blocks']) && isset($value['blocks']['blocks'])) {
                return [$key => json_encode($value)];
            }
            
            // Otherwise, assume it's a blocks array and create document
            $document = EditorJSDocumentDto::createWithBlocks($value);
            return [$key => json_encode($this->formatForDatabase($document))];
        }

        // Handle JSON strings
        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Invalid JSON provided');
                }
                
                $document = EditorJSDocumentDto::fromNestedArray($decoded ?? []);
                return [$key => json_encode($this->formatForDatabase($document))];
                
            } catch (\Exception $e) {
                throw new InvalidArgumentException("Invalid EditorJS data: {$e->getMessage()}");
            }
        }

        throw new InvalidArgumentException('EditorJS data must be an EditorJSDocumentDto instance, array, or JSON string');
    }

    /**
     * Get the serialized representation of the value
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof EditorJSDocumentDto) {
            return json_encode($this->formatForDatabase($value));
        }

        return $value;
    }

    /**
     * Get the unserialized representation of the value
     */
    public function unserialize(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return EditorJSDocumentDto::fromNestedArray($decoded ?? []);
        }

        return $value;
    }

    /**
     * Format the document for database storage
     * Maintains the complex nested structure expected by the existing database schema
     */
    private function formatForDatabase(EditorJSDocumentDto $document): array
    {
        // Create the nested structure that matches the current database format:
        // {
        //   "time": 1754710494,
        //   "blocks": {
        //     "time": 1754710493760, 
        //     "blocks": [array of blocks],
        //     "version": "2.31.0-rc.7"
        //   },
        //   "version": "2.28.2"
        // }
        
        return [
            'time' => $document->time,
            'blocks' => [
                'time' => $document->time * 1000, // Convert to milliseconds for inner timestamp
                'blocks' => $document->getBlocksAsArray(),
                'version' => $document->version,
            ],
            'version' => config('editor.version', '2.28.2'), // Outer version
        ];
    }
}