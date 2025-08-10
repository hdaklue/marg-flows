<?php

declare(strict_types=1);

namespace App\Services\Document\Casts;

use App\Services\Document\Collections\DocumentBlocksCollection;
use WendellAdriel\ValidatedDTO\Casting\Castable;

final class DocumentBlocksCast implements Castable
{
    public function cast(string $property, mixed $value): DocumentBlocksCollection
    {

        if (is_array($value)) {
            return new DocumentBlocksCollection($value);
        }

        if ($value instanceof DocumentBlocksCollection) {
            return $value;
        }

        // If it's a string, try to decode as JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                return new DocumentBlocksCollection($decoded);
            }
        }

        // Fallback to empty collection
        return new DocumentBlocksCollection([]);
    }
}
