<?php

declare(strict_types=1);

namespace App\Services\Upload\Contracts;

use App\Services\Upload\DTOs\ChunkData;
use Illuminate\Http\UploadedFile;

interface UploadStrategyContract
{
    /**
     * Handle the upload process
     */
    public function upload(UploadedFile|ChunkData $data): string;

    /**
     * Set the progress strategy for tracking upload progress
     */
    public function setProgressStrategy(ProgressStrategyContract $progressStrategy): self;

    /**
     * Set the tenant ID for multi-tenant uploads
     */
    public function forTenant(string $tenantId): self;

    /**
     * Set the storage directory relative to tenant
     */
    public function storeIn(string $directory): self;
}