<?php

declare(strict_types=1);

namespace App\Services\Upload\Contracts;

interface UploadStrategyContract
{
    /**
     * Set the progress strategy for tracking upload progress.
     */
    public function setProgressStrategy(ProgressStrategyContract $progressStrategy): self;

    /**
     * Set the tenant ID for multi-tenant uploads.
     */
    public function forTenant(string $tenantId): self;

    /**
     * Set the storage directory relative to tenant.
     */
    public function storeIn(string $directory): self;
}
