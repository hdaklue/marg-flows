<?php

namespace App\Forms\Components;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Illuminate\Support\Facades\Storage;

class ChunkedFileUpload extends BaseFileUpload
{
    use HasExtraAlpineAttributes;
    use HasPlaceholder;
    protected string $view = 'forms.components.chunked-file-upload';
    
    protected bool|\Closure $useChunking = true;
    protected int|\Closure|null $chunkSize = null;
    protected \Closure|int|null $maxParallelUploads = null;
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
    
    public function chunkSize(int|\Closure|null $bytes): static
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
            $imageConfig = config('chunked-upload.file_types.images');
            $this->acceptedFileTypes($imageConfig['accepted_types'] ?? [
                'image/jpeg',
                'image/jpg', 
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',
                'image/tiff'
            ]);
            
            // Set chunk size for images
            if (!isset($this->chunkSize)) {
                $this->chunkSize = $imageConfig['chunk_size'] ?? config('chunked-upload.default_chunk_size');
            }
            
            // Enable preview for images by default
            $this->previewable($imageConfig['previewable'] ?? true);
        }
        
        return $this;
    }
    
    public function video(bool|\Closure $video = true): static
    {
        $this->isVideoUpload = $video;
        
        if ($this->evaluate($video)) {
            $videoConfig = config('chunked-upload.file_types.videos');
            $this->acceptedFileTypes($videoConfig['accepted_types'] ?? [
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
            
            // Set chunk size for videos
            if (!isset($this->chunkSize)) {
                $this->chunkSize = $videoConfig['chunk_size'] ?? config('chunked-upload.default_chunk_size');
            }
            
            // Enable preview for videos by default
            $this->previewable($videoConfig['previewable'] ?? true);
        }
        
        return $this;
    }

    public function isChunked(): bool
    {
        return $this->evaluate($this->useChunking);
    }
    
    public function getChunkSize(): int
    {
        return $this->evaluate($this->chunkSize ?? config('chunked-upload.default_chunk_size', 5 * 1024 * 1024));
    }
    
    public function getMaxParallelUploads(): int
    {
        return $this->evaluate($this->maxParallelUploads) ?? config('chunked-upload.max_parallel_uploads', 3);
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
        return route(config('chunked-upload.routes.store', 'chunked-upload.store'));
    }
    
    public function getChunkDeleteUrl(): string
    {
        return route(config('chunked-upload.routes.delete', 'chunked-upload.delete'));
    }
    
    public function getChunkCancelUrl(): string
    {
        return route(config('chunked-upload.routes.cancel', 'chunked-upload.cancel'));
    }
    
    public function getChunkSizeFormatted(): string
    {
        return $this->formatBytes($this->getChunkSize());
    }
    
    public function getUploadTimeouts(): array
    {
        return [
            'chunk' => config('chunked-upload.timeouts.chunk_upload', 120),
            'total' => config('chunked-upload.timeouts.total_upload', 3600),
            'cleanup' => config('chunked-upload.timeouts.cleanup_delay', 300),
        ];
    }
    
    public function getStorageConfig(): array
    {
        return [
            'disk' => config('chunked-upload.storage.disk', 'public'),
            'tempDir' => config('chunked-upload.storage.temp_directory', 'uploads/temp'),
            'finalDir' => config('chunked-upload.storage.final_directory', 'uploads'),
            'autoCleanup' => config('chunked-upload.storage.auto_cleanup', true),
        ];
    }
    
    public function getSecurityConfig(): array
    {
        return [
            'validateMimeTypes' => config('chunked-upload.security.validate_mime_types', true),
            'scanForViruses' => config('chunked-upload.security.scan_for_viruses', false),
            'allowedExtensionsOnly' => config('chunked-upload.security.allowed_extensions_only', true),
            'maxFilenameLength' => config('chunked-upload.security.max_filename_length', 255),
        ];
    }
    
    public function buildFileUrl(string $fileName): string
    {
        $disk = config('chunked-upload.storage.disk', 'public');
        $finalDir = config('chunked-upload.storage.final_directory', 'uploads');
        
        // For local/public disk, use standard Laravel storage URL
        if ($disk === 'public') {
            return Storage::disk($disk)->url("{$finalDir}/{$fileName}");
        }
        
        // For other disks (S3, DO Spaces, etc.), get the proper URL
        return Storage::disk($disk)->url("{$finalDir}/{$fileName}");
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