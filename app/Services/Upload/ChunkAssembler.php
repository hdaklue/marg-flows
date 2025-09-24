<?php

declare(strict_types=1);

namespace App\Services\Upload;

use Generator;
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
        throw_if($totalChunks <= 0, new RuntimeException('Total chunks must be greater than 0'));

        // Pre-validate all chunks exist
        self::validateAllChunksExist($chunkDirectory, $totalChunks);

        // Generate unique filename for final file
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;
        $finalPath = $storeDirectory . '/' . $uniqueFileName;

        $disk = config('chunked-upload.storage.disk', 'local_chunks');

        // Ensure final directory exists
        Storage::disk($disk)->makeDirectory($storeDirectory);

        // Assemble chunks using generator for memory efficiency
        self::assembleChunksWithGenerator($disk, $chunkDirectory, $totalChunks, $finalPath);

        return $finalPath;
    }

    private static function assembleChunksWithGenerator(
        string $disk,
        string $chunkDirectory,
        int $totalChunks,
        string $finalPath,
    ): void {
        // Create a temporary file to write generator output
        $tempFile = tempnam(sys_get_temp_dir(), 'chunk_assembly_');
        if (!$tempFile) {
            throw new RuntimeException('Failed to create temporary file for chunk assembly');
        }

        $writeHandle = fopen($tempFile, 'wb');
        if (!$writeHandle) {
            unlink($tempFile);
            throw new RuntimeException('Failed to open temporary file for writing');
        }

        try {
            // Process chunks using generator pattern
            foreach (self::chunkGenerator($disk, $chunkDirectory, $totalChunks) as $buffer) {
                fwrite($writeHandle, $buffer);
            }

            fclose($writeHandle);

            // Upload the assembled file using streaming to avoid memory exhaustion
            $readHandle = fopen($tempFile, 'rb');
            if (!$readHandle) {
                throw new RuntimeException('Failed to open temporary file for reading');
            }

            try {
                Storage::disk($disk)->writeStream($finalPath, $readHandle);
            } finally {
                fclose($readHandle);
            }
        } finally {
            if (is_resource($writeHandle)) {
                fclose($writeHandle);
            }
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private static function chunkGenerator(
        string $disk,
        string $chunkDirectory,
        int $totalChunks,
    ): Generator {
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";

            if (!Storage::disk($disk)->exists($chunkPath)) {
                throw new RuntimeException("Chunk {$i} not found at {$chunkPath}");
            }

            // Yield chunk content in small pieces for memory efficiency
            $readStream = Storage::disk($disk)->readStream($chunkPath);
            if (!$readStream) {
                throw new RuntimeException("Failed to read chunk {$i} at {$chunkPath}");
            }

            try {
                while (!feof($readStream)) {
                    $buffer = fread($readStream, 8192); // 8KB buffer
                    if ($buffer !== false && $buffer !== '') {
                        yield $buffer;
                    }
                }
            } finally {
                fclose($readStream);
                // Clean up chunk immediately after processing
                Storage::disk($disk)->delete($chunkPath);
            }
        }
    }

    private static function validateAllChunksExist(string $chunkDirectory, int $totalChunks): void
    {
        $disk = config('chunked-upload.storage.disk', 'local_chunks');
        $missingChunks = [];

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (!Storage::disk($disk)->exists($chunkPath)) {
                $missingChunks[] = $i;
            }
        }

        throw_unless(
            empty($missingChunks),
            new RuntimeException('Missing chunks: ' . implode(', ', $missingChunks)),
        );
    }
}
