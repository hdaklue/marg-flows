<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\Contracts\UploadStrategyContract;
use App\Services\Upload\Strategies\Upload\SimpleUploadStrategy;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class UploadManager
{
    private ?UploadStrategyContract $uploadStrategy = null;
    private ?ProgressStrategyContract $progressStrategy = null;
    private ?string $tenantId = null;
    private ?string $directory = null;

    /**
     * Use simple upload strategy for single file uploads
     */
    public function simple(): self
    {
        $this->uploadStrategy = new SimpleUploadStrategy();
        return $this;
    }


    /**
     * Set the progress tracking strategy
     */
    public function progressStrategy(ProgressStrategyContract $progressStrategy): self
    {
        $this->progressStrategy = $progressStrategy;
        return $this;
    }

    /**
     * Set the tenant for multi-tenant uploads
     */
    public function forTenant(string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Set the storage directory relative to tenant
     */
    public function storeIn(string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * Execute the upload with the configured strategy
     */
    public function upload(UploadedFile $data): string
    {
        $this->validateConfiguration();

        // Configure the upload strategy
        $this->uploadStrategy
            ->forTenant($this->tenantId)
            ->storeIn($this->directory);

        // Set progress strategy if provided
        if ($this->progressStrategy) {
            $this->uploadStrategy->setProgressStrategy($this->progressStrategy);
        }

        // Execute the upload
        return $this->uploadStrategy->upload($data);
    }

    private function validateConfiguration(): void
    {
        if (!$this->uploadStrategy) {
            throw new InvalidArgumentException('Upload strategy is required. Call simple() first.');
        }

        if (!$this->tenantId) {
            throw new InvalidArgumentException('Tenant ID is required. Call forTenant($tenantId) first.');
        }

        if (!$this->directory) {
            throw new InvalidArgumentException('Storage directory is required. Call storeIn($directory) first.');
        }
    }
}