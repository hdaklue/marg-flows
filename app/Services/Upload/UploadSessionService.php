<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Services\Directory\Managers\ChunksDirectoryManager;
use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\DTOs\ChunkData;
use App\Services\Upload\DTOs\ProgressData;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;

// Todo: should start assembly queue
final class UploadSessionService
{
    private ?string $tenantId = null;

    private ?string $chunkDirectory = null;

    private ?string $storeDirectory = null;

    public function __construct(
        private readonly ProgressStrategyContract $progressStrategy,
    ) {}

    /**
     * Set the tenant for multi-tenant uploads.
     * Automatically configures chunk directory using DirectoryManager.
     */
    public function forTenant(string $tenantId): self
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    /**
     * Set custom chunk storage directory.
     */
    public function setChunkDirectory(string $directory): self
    {
        $this->chunkDirectory = $directory;

        return $this;
    }

    /**
     * Set final file storage directory.
     */
    public function storeIn(string $directory): self
    {
        $this->storeDirectory = $directory;

        return $this;
    }

    /**
     * Initialize a new upload session.
     */
    public function initSession(
        string $fileName,
        int $totalChunks,
        ?int $totalSize = null,
    ): string {
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
     * Store a single chunk.
     */
    public function storeChunk(string $sessionId, File $chunk, int $chunkIndex): void
    {
        $this->validateConfiguration();

        $chunkDirectory = $this->getChunkDirectory($sessionId);
        $disk = config('chunked-upload.storage.disk', 'local_chunks');

        // Ensure chunk directory exists
        Storage::disk($disk)->makeDirectory($chunkDirectory);

        // Store the chunk file
        $chunkFilename = "chunk_{$chunkIndex}";
        if ($chunk instanceof UploadedFile) {
            $chunk->storeAs($chunkDirectory, $chunkFilename, [
                'disk' => $disk,
            ]);
        } else {
            // For regular File instances, copy to storage
            $chunkPath = $chunkDirectory . '/' . $chunkFilename;
            Storage::disk($disk)->put($chunkPath, file_get_contents($chunk->getPathname()));
        }

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
            ],
        ));
    }

    /**
     * Process multiple chunks at once (for WebSocket fire-and-forget).
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
                if (! $chunk->uploaded) {
                    $this->processChunk($chunkData->sessionId, $chunk, $chunkDirectory);

                    // Update progress
                    $this->progressStrategy->updateProgress($chunkData->sessionId, new ProgressData(
                        completedChunks: $chunk->index + 1,
                        totalChunks: $chunkData->getTotalChunks(),
                        bytesUploaded: $chunkData->getUploadedBytes(),
                        totalBytes: $chunkData->totalSize,
                        percentage: $chunkData->getProgress(),
                        status: 'uploading',
                        currentChunk: $chunk->toArray(),
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
     * Check if all chunks for a session are uploaded.
     */
    public function isComplete(string $sessionId, int $totalChunks): bool
    {
        $chunkDirectory = $this->getChunkDirectory($sessionId);
        $disk = config('chunked-upload.storage.disk', 'local_chunks');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (! Storage::disk($disk)->exists($chunkPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assemble all chunks into final file.
     */
    public function assembleFile(string $sessionId, string $fileName, int $totalChunks): string
    {
        $this->validateConfiguration();

        $finalPath = ChunkAssembler::assemble(
            $sessionId,
            $fileName,
            $totalChunks,
            $this->getChunkDirectory($sessionId),
            $this->storeDirectory,
        );

        $disk = config('chunked-upload.storage.disk', 'local_chunks');

        // Mark as completed
        $this->progressStrategy->complete($sessionId, [
            'path' => $finalPath,
            'url' => Storage::disk($disk)->url($finalPath),
        ]);

        return $finalPath;
    }

    /**
     * Clean up session chunks.
     */
    public function cleanupSession(string $sessionId): void
    {
        if (! $this->tenantId) {
            return;
        }

        // Clean up file chunks
        ChunksDirectoryManager::forTenant($this->tenantId)
            ->chunks($this->tenantId)
            ->forSession($sessionId)
            ->deleteSession();

        // Clean up progress tracking data
        $this->progressStrategy->cleanup($sessionId);
    }

    /**
     * Get progress information for a session.
     */
    public function getProgress(string $sessionId): ?ProgressData
    {
        return $this->progressStrategy->getProgress($sessionId);
    }

    /**
     * Upload a file or process chunk data.
     * Handles both single files (as 1-chunk upload) and chunked data.
     */
    public function upload(File|ChunkData $data): string
    {
        if ($data instanceof File) {
            return $this->uploadSingleFile($data);
        }

        return $this->processChunks($data);
    }

    /**
     * Upload a single file as a 1-chunk upload.
     */
    private function uploadSingleFile(File $file): string
    {
        $this->validateConfiguration();

        $fileName = $file instanceof UploadedFile
            ? $file->getClientOriginalName()
            : $file->getFilename();
        $sessionId = $this->initSession($fileName, 1, $file->getSize());
        $this->storeChunk($sessionId, $file, 0);
        $finalPath = $this->assembleFile($sessionId, $fileName, 1);
        $this->cleanupSession($sessionId);

        return $finalPath;
    }

    private function getChunkDirectory(string $sessionId): string
    {
        if ($this->chunkDirectory) {
            return $this->chunkDirectory;
        }

        // Use DirectoryManager chunks strategy for organized chunk storage
        return ChunksDirectoryManager::forTenant($this->tenantId)
            ->chunks($this->tenantId)
            ->forSession($sessionId)
            ->getDirectory();
    }

    private function processChunk(string $sessionId, $chunk, string $chunkDirectory): void
    {
        $disk = config('chunked-upload.storage.disk', 'local_chunks');

        // Ensure chunk directory exists
        Storage::disk($disk)->makeDirectory($chunkDirectory);

        // Store chunk - this is placeholder for actual chunk processing
        $chunkPath = "{$chunkDirectory}/chunk_{$chunk->index}";
        Storage::disk($disk)->put($chunkPath, "chunk_data_placeholder_{$chunk->index}");
    }

    private function assembleChunks(ChunkData $chunkData, string $chunkDirectory): string
    {
        $disk = config('chunked-upload.storage.disk', 'local_chunks');

        $extension = pathinfo($chunkData->fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;
        $finalPath = $this->storeDirectory . '/' . $uniqueFileName;

        // Ensure final directory exists
        Storage::disk($disk)->makeDirectory($this->storeDirectory);

        // Assemble chunks into final file
        $finalContent = '';
        for ($i = 0; $i < $chunkData->getTotalChunks(); $i++) {
            $chunkPath = "{$chunkDirectory}/chunk_{$i}";
            if (Storage::disk($disk)->exists($chunkPath)) {
                $finalContent .= Storage::disk($disk)->get($chunkPath);
            }
        }

        Storage::disk($disk)->put($finalPath, $finalContent);

        return $finalPath;
    }

    private function cleanupChunks(string $chunkDirectory): void
    {
        if (! $this->tenantId) {
            return;
        }

        $sessionId = basename($chunkDirectory);
        ChunksDirectoryManager::forTenant($this->tenantId)
            ->chunks($this->tenantId)
            ->forSession($sessionId)
            ->deleteSession();
    }

    private function validateConfiguration(): void
    {
        throw_unless(
            $this->tenantId,
            new InvalidArgumentException('Tenant ID is required. Call forTenant($tenantId) first.'),
        );

        throw_unless(
            $this->storeDirectory,
            new InvalidArgumentException(
                'Storage directory is required. Call storeIn($directory) first.',
            ),
        );
    }
}
