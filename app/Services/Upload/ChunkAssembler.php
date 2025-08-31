<?php

declare(strict_types=1);

namespace App\Services\Upload;

use Exception;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ChunkAssembler
{
    private const int BUFFER_SIZE = 65536; // 64KB for optimal performance

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

        // Ensure final directory exists
        Storage::makeDirectory($storeDirectory);

        // Assemble chunks with optimized streaming
        $finalFullPath = Storage::path($finalPath);
        $finalHandle = fopen($finalFullPath, 'wb');

        throw_unless($finalHandle, new RuntimeException('Cannot create final file: ' . $finalPath));

        try {
            self::streamChunksToFile($finalHandle, $chunkDirectory, $totalChunks);

            return $finalPath;
        } catch (Exception $e) {
            // Cleanup failed assembly
            if (file_exists($finalFullPath)) {
                unlink($finalFullPath);
            }
            throw $e;
        } finally {
            fclose($finalHandle);
        }
    }

    private static function validateAllChunksExist(string $chunkDirectory, int $totalChunks): void
    {
        $missingChunks = [];

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (! Storage::exists($chunkPath)) {
                $missingChunks[] = $i;
            }
        }

        throw_unless(empty($missingChunks), new RuntimeException('Missing chunks: ' . implode(', ', $missingChunks)));
    }

    private static function streamChunksToFile($finalHandle, string $chunkDirectory, int $totalChunks): void
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            $chunkFullPath = Storage::path($chunkPath);

            self::copyChunkToFile($finalHandle, $chunkFullPath, $i);
        }
    }

    private static function copyChunkToFile($finalHandle, string $chunkFullPath, int $chunkIndex): void
    {
        $chunkHandle = fopen($chunkFullPath, 'rb');

        throw_unless($chunkHandle, new RuntimeException("Cannot read chunk {$chunkIndex}: {$chunkFullPath}"));

        try {
            // Use optimized buffer size for better I/O performance
            while (! feof($chunkHandle)) {
                $data = fread($chunkHandle, self::BUFFER_SIZE);

                throw_if($data === false, new RuntimeException("Failed to read from chunk {$chunkIndex}"));

                $bytesWritten = fwrite($finalHandle, $data);

                throw_if($bytesWritten === false || $bytesWritten !== strlen($data), new RuntimeException("Failed to write chunk {$chunkIndex} data"));
            }
        } finally {
            fclose($chunkHandle);
        }
    }
}
