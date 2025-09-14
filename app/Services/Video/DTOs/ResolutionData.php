<?php

declare(strict_types=1);

namespace App\Services\Video\DTOs;

use App\Support\FileSize;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class ResolutionData extends SimpleDTO
{
    public string $conversion;

    public ?string $output_path;

    public string $status;

    public int $size;

    public ?string $error;

    /**
     * Create successful conversion result.
     */
    public static function success(
        string $conversion,
        string $outputPath,
        int $size,
    ): self {
        return new self([
            'conversion' => $conversion,
            'output_path' => $outputPath,
            'status' => 'success',
            'size' => $size,
        ]);
    }

    /**
     * Create failed conversion result.
     */
    public static function failed(
        string $conversion,
        string $error,
        ?string $outputPath = null,
    ): self {
        return new self([
            'conversion' => $conversion,
            'output_path' => $outputPath,
            'status' => 'failed',
            'size' => 0,
            'error' => $error,
        ]);
    }

    /**
     * Check if conversion was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if conversion failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get file size in human readable format.
     */
    public function getHumanFileSize(): string
    {
        return FileSize::format($this->size);
    }

    /**
     * Get conversion class name without namespace.
     */
    public function getConversionName(): string
    {
        return class_basename($this->conversion);
    }

    /**
     * Get output filename from path.
     */
    public function getOutputFilename(): ?string
    {
        return $this->output_path ? basename($this->output_path) : null;
    }

    /**
     * Define the type casting for the DTO properties.
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Define the default values for the DTO properties.
     */
    protected function defaults(): array
    {
        return [
            'status' => 'success',
            'size' => 0,
            'output_path' => null,
            'error' => null,
        ];
    }
}
