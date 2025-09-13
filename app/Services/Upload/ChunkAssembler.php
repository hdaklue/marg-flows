<?php

declare(strict_types=1);

namespace App\Services\Upload;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ChunkAssembler
{
    public static function assemble(
        string $sessionId,
        string $fileName,
        int $totalChunks,
        string $chunkDirectory,
        string $storeDirectory,
    ): string {
        // Validate inputs
        throw_if(
            $totalChunks <= 0,
            new RuntimeException('Total chunks must be greater than 0'),
        );

        // Pre-validate all chunks exist
        self::validateAllChunksExist($chunkDirectory, $totalChunks);

        // Generate unique filename for final file
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;
        $finalPath = $storeDirectory . '/' . $uniqueFileName;

        $disk = config('chunked-upload.storage.disk', 'public');

        // Ensure final directory exists
        Storage::disk($disk)->makeDirectory($storeDirectory);

        // Assemble chunks using Storage facade for cloud compatibility
        $finalContent = '';

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";

            if (! Storage::disk($disk)->exists($chunkPath)) {
                throw new RuntimeException("Chunk {$i} not found at {$chunkPath}");
            }

            $finalContent .= Storage::disk($disk)->get($chunkPath);
        }

        // Store the assembled file
        Storage::disk($disk)->put($finalPath, $finalContent);

        return $finalPath;
    }

    private static function validateAllChunksExist(
        string $chunkDirectory,
        int $totalChunks,
    ): void {
        $disk = config('chunked-upload.storage.disk', 'public');
        $missingChunks = [];

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (! Storage::disk($disk)->exists($chunkPath)) {
                $missingChunks[] = $i;
            }
        }

        throw_unless(
            empty($missingChunks),
            new RuntimeException('Missing chunks: '
            . implode(', ', $missingChunks)),
        );
    }
}
