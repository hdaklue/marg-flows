<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

use Illuminate\Http\UploadedFile;

interface StorageStrategyContract
{
    public function forTenant(string $tenantId): self;

    public function store(UploadedFile $file): string;

    public function getUrl(): string;

    public function getDirectory(): string;

    public function delete(string $path): bool;
}