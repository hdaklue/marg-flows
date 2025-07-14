<?php

namespace App\Forms\Components;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Filament\Forms\Components\Concerns\HasPlaceholder;

class ChunkedFileUpload extends BaseFileUpload
{
    use HasExtraAlpineAttributes;
    use HasPlaceholder;
    protected string $view = 'forms.components.chunked-file-upload';
    
    protected bool|\Closure $useChunking = true;
    protected int|\Closure $chunkSize = 5 * 1024 * 1024; // 5MB default
    protected \Closure|int|null $maxParallelUploads = 3;
    protected \Closure|string|null $alignment = null;
    protected bool|\Closure $previewable = false;
    protected bool|\Closure $isImageUpload = false;
    protected bool|\Closure $isVideoUpload = false;
    
    // Default to multiple files for chunked uploads (always returns array)
    protected bool|\Closure $isMultiple = true;
    
    public function chunked(bool|\Closure $chunked = true): static
    {
        $this->useChunking = $chunked;
        return $this;
    }
    
    public function chunkSize(int|\Closure $bytes): static
    {
        $this->chunkSize = $bytes;
        return $this;
    }
    
    public function maxParallelUploads(int|\Closure|null $count): static
    {
        $this->maxParallelUploads = $count;
        return $this;
    }
    
    public function alignment(string|\Closure|null $alignment): static
    {
        $this->alignment = $alignment;
        return $this;
    }
    
    public function previewable(bool|\Closure $previewable = true): static
    {
        $this->previewable = $previewable;
        return $this;
    }
    
    public function image(bool|\Closure $image = true): static
    {
        $this->isImageUpload = $image;
        
        if ($this->evaluate($image)) {
            $this->acceptedFileTypes([
                'image/jpeg',
                'image/jpg', 
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',
                'image/tiff'
            ]);
            // Enable preview for images by default
            $this->previewable(true);
        }
        
        return $this;
    }
    
    public function video(bool|\Closure $video = true): static
    {
        $this->isVideoUpload = $video;
        
        if ($this->evaluate($video)) {
            $this->acceptedFileTypes([
                'video/mp4',
                'video/mpeg',
                'video/quicktime',
                'video/x-msvideo', // .avi
                'video/x-ms-wmv',  // .wmv
                'video/webm',
                'video/ogg',
                'video/3gpp',
                'video/x-flv'
            ]);
            // Enable preview for videos by default
            $this->previewable(true);
        }
        
        return $this;
    }

    public function isChunked(): bool
    {
        return $this->evaluate($this->useChunking);
    }
    
    public function getChunkSize(): int
    {
        return $this->evaluate($this->chunkSize);
    }
    
    public function getMaxParallelUploads(): int
    {
        return $this->evaluate($this->maxParallelUploads) ?? 3;
    }
    
    public function getAlignment(): string|null
    {
        return $this->evaluate($this->alignment);
    }
    
    public function isPreviewable(): bool
    {
        return $this->evaluate($this->previewable);
    }
    
    public function isImageUpload(): bool
    {
        return $this->evaluate($this->isImageUpload);
    }
    
    public function isVideoUpload(): bool
    {
        return $this->evaluate($this->isVideoUpload);
    }

    public function getChunkUploadUrl(): string
    {
        return route('chunked-upload.store');
    }
    
    public function getChunkDeleteUrl(): string
    {
        return route('chunked-upload.delete');
    }
    
    public function getChunkCancelUrl(): string
    {
        return route('chunked-upload.cancel');
    }
    
    public function getChunkSizeFormatted(): string
    {
        return $this->formatBytes($this->chunkSize);
    }
    
    // Override saveUploadedFiles to handle chunked files
    public function saveUploadedFiles(): void
    {
        // For chunked uploads, files are already saved by the controller
        // No additional processing needed since they're already in final location
        return;
    }
    
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}