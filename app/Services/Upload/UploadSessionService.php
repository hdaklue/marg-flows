<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Services\Directory\Facades\DirectoryManager;
use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\DTOs\ChunkData;
use App\Services\Upload\DTOs\ProgressData;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class UploadSessionService
{
    private ?string $tenantId = null;
    private ?string $chunkDirectory = null;
    private ?string $storeDirectory = null;

    public function __construct(
        private readonly ProgressStrategyContract $progressStrategy
    ) {}

    /**
     * Set the tenant for multi-tenant uploads
     */
    public function forTenant(string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Set custom chunk storage directory
     */
    public function setChunkDirectory(string $directory): self
    {
        $this->chunkDirectory = $directory;
        return $this;
    }

    /**
     * Set final file storage directory
     */
    public function storeIn(string $directory): self
    {
        $this->storeDirectory = $directory;
        return $this;
    }

    /**
     * Initialize a new upload session
     */
    public function initSession(string $fileName, int $totalChunks, ?int $totalSize = null): string
    {
        $this->validateConfiguration();

        $sessionId = uniqid('upload_', true);

        // Initialize progress tracking
        $this->progressStrategy->init($sessionId, [
            'totalChunks' => $totalChunks,
            'totalBytes' => $totalSize,
            'fileName' => $fileName,
            'status' => 'initialized',
        ]);

        return $sessionId;
    }

    /**
     * Store a single chunk
     */
    public function storeChunk(string $sessionId, UploadedFile $chunk, int $chunkIndex): void
    {
        $this->validateConfiguration();

        $chunkDirectory = $this->getChunkDirectory($sessionId);
        
        // Ensure chunk directory exists
        Storage::makeDirectory($chunkDirectory);
        
        // Store the chunk file
        $chunkFilename = "chunk_{$chunkIndex}";
        $chunk->storeAs($chunkDirectory, $chunkFilename);

        // Update progress
        $this->progressStrategy->updateProgress($sessionId, new ProgressData(
            completedChunks: $chunkIndex + 1,
            totalChunks: 0, // Will be filled by progress strategy if needed
            bytesUploaded: 0, // Will be calculated if needed
            totalBytes: 0,
            percentage: 0,
            status: 'uploading',
            currentChunk: [
                'index' => $chunkIndex,
                'stored' => true,
            ]
        ));
    }

    /**
     * Process multiple chunks at once (for WebSocket fire-and-forget)
     */
    public function processChunks(ChunkData $chunkData): string
    {
        $this->validateConfiguration();

        try {
            // Initialize progress tracking
            $this->progressStrategy->init($chunkData->sessionId, [
                'totalChunks' => $chunkData->getTotalChunks(),
                'totalBytes' => $chunkData->totalSize,
                'fileName' => $chunkData->fileName,
            ]);

            $chunkDirectory = $this->getChunkDirectory($chunkData->sessionId);

            // Process chunks sequentially
            foreach ($chunkData->chunks as $chunk) {
                if (!$chunk->uploaded) {
                    $this->processChunk($chunkData->sessionId, $chunk, $chunkDirectory);
                    
                    // Update progress
                    $this->progressStrategy->updateProgress($chunkData->sessionId, new ProgressData(
                        completedChunks: $chunk->index + 1,
                        totalChunks: $chunkData->getTotalChunks(),
                        bytesUploaded: $chunkData->getUploadedBytes(),
                        totalBytes: $chunkData->totalSize,
                        percentage: $chunkData->getProgress(),
                        status: 'uploading',
                        currentChunk: $chunk->toArray()
                    ));
                }
            }

            // Assemble final file
            $finalPath = $this->assembleChunks($chunkData, $chunkDirectory);

            // Clean up chunks
            $this->cleanupChunks($chunkDirectory);

            // Mark as completed
            $this->progressStrategy->complete($chunkData->sessionId, [
                'path' => $finalPath,
                'url' => Storage::url($finalPath),
            ]);

            return $finalPath;

        } catch (Exception $e) {
            $this->progressStrategy->error($chunkData->sessionId, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if all chunks for a session are uploaded
     */
    public function isComplete(string $sessionId, int $totalChunks): bool
    {
        $chunkDirectory = $this->getChunkDirectory($sessionId);
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (!Storage::exists($chunkPath)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Assemble all chunks into final file
     */
    public function assembleFile(string $sessionId, string $fileName, int $totalChunks): string
    {
        $this->validateConfiguration();

        $chunkDirectory = $this->getChunkDirectory($sessionId);
        
        // Generate unique filename for final file
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;
        $finalPath = $this->storeDirectory . '/' . $uniqueFileName;

        // Ensure final directory exists
        Storage::makeDirectory($this->storeDirectory);

        // Assemble chunks
        $finalFullPath = Storage::path($finalPath);
        $finalHandle = fopen($finalFullPath, 'wb');
        
        if (!$finalHandle) {
            throw new Exception('Cannot create final file');
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = "{$chunkDirectory}/chunk_{$i}";
                
                if (!Storage::exists($chunkPath)) {
                    throw new Exception("Missing chunk {$i}");
                }

                $chunkFullPath = Storage::path($chunkPath);
                $chunkHandle = fopen($chunkFullPath, 'rb');
                
                if (!$chunkHandle) {
                    throw new Exception("Cannot read chunk {$i}");
                }

                while (!feof($chunkHandle)) {
                    $data = fread($chunkHandle, 8192);
                    fwrite($finalHandle, $data);
                }
                
                fclose($chunkHandle);
            }

            // Mark as completed
            $this->progressStrategy->complete($sessionId, [
                'path' => $finalPath,
                'url' => Storage::url($finalPath),
            ]);

            return $finalPath;

        } finally {
            fclose($finalHandle);
        }
    }

    /**
     * Clean up session chunks
     */
    public function cleanupSession(string $sessionId): void
    {
        if (!$this->tenantId) {
            return;
        }

        // Clean up file chunks
        DirectoryManager::chunks()
            ->forTenant($this->tenantId)
            ->forSession($sessionId)
            ->deleteSession();

        // Clean up progress tracking data
        $this->progressStrategy->cleanup($sessionId);
    }

    /**
     * Get progress information for a session
     */
    public function getProgress(string $sessionId): ?ProgressData
    {
        return $this->progressStrategy->getProgress($sessionId);
    }

    private function getChunkDirectory(string $sessionId): string
    {
        if ($this->chunkDirectory) {
            return $this->chunkDirectory;
        }

        // Use DirectoryManager chunks strategy for organized chunk storage
        return DirectoryManager::chunks()
            ->forTenant($this->tenantId)
            ->forSession($sessionId)
            ->getDirectory();
    }

    private function processChunk(string $sessionId, $chunk, string $chunkDirectory): void
    {
        // Ensure chunk directory exists
        Storage::makeDirectory($chunkDirectory);
        
        // Store chunk - this is placeholder for actual chunk processing
        $chunkPath = "{$chunkDirectory}/chunk_{$chunk->index}";
        Storage::put($chunkPath, "chunk_data_placeholder_{$chunk->index}");
    }

    private function assembleChunks(ChunkData $chunkData, string $chunkDirectory): string
    {
        $extension = pathinfo($chunkData->fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;
        $finalPath = $this->storeDirectory . '/' . $uniqueFileName;

        // Ensure final directory exists
        Storage::makeDirectory($this->storeDirectory);

        // Assemble chunks into final file
        $finalContent = '';
        for ($i = 0; $i < $chunkData->getTotalChunks(); $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (Storage::exists($chunkPath)) {
                $finalContent .= Storage::get($chunkPath);
            }
        }

        Storage::put($finalPath, $finalContent);
        return $finalPath;
    }

    private function cleanupChunks(string $chunkDirectory): void
    {
        if (!$this->tenantId) {
            return;
        }

        $sessionId = basename($chunkDirectory);
        DirectoryManager::chunks()
            ->forTenant($this->tenantId)
            ->forSession($sessionId)
            ->deleteSession();
    }

    private function validateConfiguration(): void
    {
        if (!$this->tenantId) {
            throw new InvalidArgumentException('Tenant ID is required. Call forTenant($tenantId) first.');
        }

        if (!$this->storeDirectory) {
            throw new InvalidArgumentException('Storage directory is required. Call storeIn($directory) first.');
        }
    }
}