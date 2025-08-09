<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\EditorJS\EditorJSData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Custom cast for EditorJS data
 * Handles conversion between database JSON and EditorJSData value objects
 */
class EditorJSCast implements CastsAttributes
{
    /**
     * Cast the given value from the database
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EditorJSData
    {
        if ($value === null) {
            return null;
        }

        // Handle empty strings or empty arrays
        if ($value === '' || $value === '[]' || $value === '{}') {
            return EditorJSData::empty();
        }

        try {
            // Parse JSON if it's a string
            $data = is_string($value) ? json_decode($value, true) : $value;
            
            if (!is_array($data)) {
                throw new InvalidArgumentException('EditorJS data must be an array or valid JSON');
            }

            return EditorJSData::fromArray($data);
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
            
            return EditorJSData::empty();
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

        // Handle EditorJSData value objects
        if ($value instanceof EditorJSData) {
            return [$key => json_encode($value->toArray())];
        }

        // Handle arrays
        if (is_array($value)) {
            // If it's already in the correct EditorJS format, store as-is
            if (isset($value['blocks'], $value['time'], $value['version'])) {
                return [$key => json_encode($value)];
            }
            
            // Otherwise, assume it's a blocks array and wrap it
            $editorData = EditorJSData::create($value);
            return [$key => json_encode($editorData->toArray())];
        }

        // Handle JSON strings
        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Invalid JSON provided');
                }
                
                $editorData = EditorJSData::fromArray($decoded ?? []);
                return [$key => json_encode($editorData->toArray())];
            } catch (\Exception $e) {
                throw new InvalidArgumentException("Invalid EditorJS data: {$e->getMessage()}");
            }
        }

        throw new InvalidArgumentException('EditorJS data must be an EditorJSData instance, array, or JSON string');
    }

    /**
     * Get the serialized representation of the value
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value instanceof EditorJSData ? $value->toJson() : $value;
    }

    /**
     * Get the unserialized representation of the value
     */
    public function unserialize(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return is_string($value) ? EditorJSData::fromJson($value) : $value;
    }
}