<?php

declare(strict_types=1);

namespace App\Services\Upload\Strategies\Upload;

use App\Services\Assets\Facades\AssetsManager;
use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\Contracts\UploadStrategyContract;
use App\Services\Upload\DTOs\ChunkData;
use App\Services\Upload\DTOs\ProgressData;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class ChunkedUploadStrategy implements UploadStrategyContract
{
    private ?ProgressStrategyContract $progressStrategy = null;
    private ?string $tenantId = null;
    private ?string $directory = null;

    public function upload(UploadedFile|ChunkData $data): string
    {
        if ($data instanceof UploadedFile) {
            throw new InvalidArgumentException('UploadedFile not supported in ChunkedUploadStrategy. Use SimpleUploadStrategy instead.');
        }

        if (!$data instanceof ChunkData) {
            throw new InvalidArgumentException('ChunkedUploadStrategy expects a ChunkData instance.');
        }

        $this->validateConfiguration();

        try {
            // Initialize progress tracking
            if ($this->progressStrategy) {
                $this->progressStrategy->init($data->sessionId, [
                    'totalChunks' => $data->getTotalChunks(),
                    'totalBytes' => $data->totalSize,
                    'fileName' => $data->fileName,
                ]);
            }

            // Process chunks sequentially
            $processedChunkData = $data;
            $chunkDirectory = $this->getChunkDirectory($data->sessionId);

            foreach ($data->chunks as $chunk) {
                if (!$chunk->uploaded) {
                    // Process individual chunk
                    $this->processChunk($data->sessionId, $chunk, $chunkDirectory);
                    
                    // Mark chunk as uploaded
                    $processedChunkData = $processedChunkData->markChunkAsUploaded($chunk->index);

                    // Update progress
                    if ($this->progressStrategy) {
                        $this->progressStrategy->updateProgress($data->sessionId, new ProgressData(
                            completedChunks: $processedChunkData->getCompletedChunks(),
                            totalChunks: $processedChunkData->getTotalChunks(),
                            bytesUploaded: $processedChunkData->getUploadedBytes(),
                            totalBytes: $processedChunkData->totalSize,
                            percentage: $processedChunkData->getProgress(),
                            status: 'uploading',
                            currentChunk: $chunk->toArray()
                        ));
                    }
                }
            }

            // All chunks uploaded, now assemble the final file
            $finalPath = $this->assembleChunks($processedChunkData, $chunkDirectory);

            // Clean up chunk files
            $this->cleanupChunks($chunkDirectory);

            // Update progress to completed
            if ($this->progressStrategy) {
                $this->progressStrategy->complete($data->sessionId, [
                    'path' => $finalPath,
                    'url' => Storage::url($finalPath),
                ]);
            }

            return $finalPath;

        } catch (Exception $e) {
            // Update progress to error
            if ($this->progressStrategy) {
                $this->progressStrategy->error($data->sessionId, $e->getMessage());
            }

            throw $e;
        }
    }

    public function setProgressStrategy(ProgressStrategyContract $progressStrategy): self
    {
        $this->progressStrategy = $progressStrategy;
        return $this;
    }

    public function forTenant(string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function storeIn(string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    private function getChunkDirectory(string $sessionId): string
    {
        // Use AssetsManager chunks strategy for organized chunk storage
        return AssetsManager::chunks()
            ->forTenant($this->tenantId)
            ->forSession($sessionId)
            ->getDirectory();
    }

    private function processChunk(string $sessionId, $chunk, string $chunkDirectory): void
    {
        // This would be called for each individual chunk upload
        // In a real implementation, this would receive the actual chunk file
        // For now, this is a placeholder for the chunk processing logic
        
        // Ensure chunk directory exists
        Storage::makeDirectory($chunkDirectory);
        
        // In actual implementation:
        // 1. Validate chunk hash
        // 2. Store chunk file
        // 3. Verify chunk integrity
        
        // Placeholder: Create a temporary chunk file
        $chunkPath = "{$chunkDirectory}/chunk_{$chunk->index}";
        Storage::put($chunkPath, "chunk_data_placeholder_{$chunk->index}");
    }

    private function assembleChunks(ChunkData $chunkData, string $chunkDirectory): string
    {
        if (!$this->directory) {
            throw new InvalidArgumentException('Storage directory is required. Call storeIn($directory) first.');
        }

        // Use the provided directory for final file storage
        $finalPath = "{$this->directory}/{$chunkData->fileName}";

        // Ensure final directory exists
        Storage::makeDirectory($this->directory);

        // Assemble chunks into final file
        $finalContent = '';
        for ($i = 0; $i < $chunkData->getTotalChunks(); $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (Storage::exists($chunkPath)) {
                $finalContent .= Storage::get($chunkPath);
            }
        }

        // Store the final assembled file
        Storage::put($finalPath, $finalContent);

        return $finalPath;
    }

    private function cleanupChunks(string $chunkDirectory): void
    {
        // Clean up temporary chunk files using AssetsManager
        $sessionId = basename($chunkDirectory); // Extract session ID from path
        AssetsManager::chunks()
            ->forTenant($this->tenantId)
            ->forSession($sessionId)
            ->deleteSession();
    }

    private function validateConfiguration(): void
    {
        if (!$this->tenantId) {
            throw new InvalidArgumentException('Tenant ID is required. Call forTenant($tenantId) first.');
        }

        if (!$this->directory) {
            throw new InvalidArgumentException('Storage directory is required. Call storeIn($directory) first.');
        }
    }
}