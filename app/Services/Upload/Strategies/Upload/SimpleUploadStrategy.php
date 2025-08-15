<?php

declare(strict_types=1);

namespace App\Services\Upload\Strategies\Upload;

use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\Contracts\UploadStrategyContract;
use App\Services\Upload\DTOs\ChunkData;
use App\Services\Upload\DTOs\ProgressData;
use Exception;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class SimpleUploadStrategy implements UploadStrategyContract
{
    private ?ProgressStrategyContract $progressStrategy = null;
    private ?string $tenantId = null;
    private ?string $directory = null;

    public function upload(UploadedFile|ChunkData $data): string
    {
        if ($data instanceof ChunkData) {
            throw new InvalidArgumentException('ChunkData not supported in SimpleUploadStrategy. Use ChunkedUploadStrategy instead.');
        }

        if (!$data instanceof UploadedFile) {
            throw new InvalidArgumentException('SimpleUploadStrategy expects an UploadedFile instance.');
        }

        $this->validateConfiguration();

        $sessionId = uniqid('simple_upload_', true);

        try {
            // Initialize progress tracking
            if ($this->progressStrategy) {
                $this->progressStrategy->init($sessionId, [
                    'totalChunks' => 1,
                    'totalBytes' => $data->getSize(),
                    'fileName' => $data->getClientOriginalName(),
                ]);

                // Update progress to uploading
                $this->progressStrategy->updateProgress($sessionId, new ProgressData(
                    completedChunks: 0,
                    totalChunks: 1,
                    bytesUploaded: 0,
                    totalBytes: $data->getSize(),
                    percentage: 0.0,
                    status: 'uploading'
                ));
            }

            // Store file in the provided directory
            $filename = uniqid() . '_' . time() . '.' . $data->getClientOriginalExtension();
            $path = $data->storeAs($this->directory, $filename);

            // Update progress to completed
            if ($this->progressStrategy) {
                $this->progressStrategy->complete($sessionId, [
                    'path' => $path,
                ]);
            }

            return $path;

        } catch (Exception $e) {
            // Update progress to error
            if ($this->progressStrategy) {
                $this->progressStrategy->error($sessionId, $e->getMessage());
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