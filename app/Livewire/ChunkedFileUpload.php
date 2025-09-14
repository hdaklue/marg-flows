<?php

declare(strict_types=1);

namespace App\Livewire;

use Exception;
use Livewire\Component;

final class ChunkedFileUpload extends Component
{
    // Component configuration
    public bool $modalMode = false;

    public bool $allowUrlImport = false;

    public bool $multiple = true;

    public array $acceptedFileTypes = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'video/mp4',
    ];

    public int $chunkSize = 5242880; // 5MB

    public int $maxFiles = 10;

    public int $maxFileSize = 52428800; // 50MB

    public int $minFileSize = 0;

    public int $maxParallelUploads = 3;

    public string $statePath = 'files';

    public string $disk = 'public';

    public string $finalDir = 'uploads';

    public string $tempDir = 'uploads/temp';

    public ?string $importUrlEndpoint = null;

    // Component state
    public array $uploadedFiles = [];

    public bool $showModal = false;

    // Enhanced upload tracking
    public array $activeUploads = [];

    public bool $uploading = false;

    public ?string $error = null;

    public ?string $success = null;

    // Props for external configuration
    public function mount(
        ?bool $modalMode = false,
        ?bool $allowUrlImport = false,
        ?bool $multiple = true,
        ?array $acceptedFileTypes = null,
        ?int $maxFiles = null,
        ?int $maxFileSize = null,
        ?int $minFileSize = null,
        ?int $maxParallelUploads = null,
        ?string $statePath = null,
        ?string $disk = null,
        ?string $finalDir = null,
        ?string $tempDir = null,
        ?string $importUrlEndpoint = null,
    ): void {
        $this->modalMode = $modalMode ?? false;
        $this->allowUrlImport = $allowUrlImport ?? false;
        $this->multiple = $multiple ?? true;
        $this->acceptedFileTypes = $acceptedFileTypes ?? [
            'image/jpeg',
            'image/png',
            'application/pdf',
            'video/mp4',
        ];
        $this->maxFiles = $maxFiles ?? 10;
        $this->maxFileSize = $maxFileSize ?? 52428800; // 50MB
        $this->minFileSize = $minFileSize ?? 0;
        $this->maxParallelUploads = $maxParallelUploads ?? 3;
        $this->statePath = $statePath ?? 'files';
        $this->disk = $disk ?? 'public';
        $this->finalDir = $finalDir ?? 'uploads';
        $this->tempDir = $tempDir ?? 'uploads/temp';
        $this->importUrlEndpoint = $importUrlEndpoint;
    }

    // Modal methods
    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    // Add uploaded file to Livewire state
    public function addUploadedFile(array $fileData): void
    {
        $this->uploadedFiles[] = [
            'key' => $fileData['key'] ?? uniqid(),
            'name' => $fileData['name'] ?? 'Unknown',
            'path' => $fileData['path'] ?? '',
            'url' => $fileData['url'] ?? '',
            'size' => $fileData['size'] ?? 0,
            'type' => $fileData['type'] ?? 'application/octet-stream',
            'uploaded_at' => now()->toISOString(),
            'imported' => $fileData['imported'] ?? false,
        ];

        $this->clearMessages();
        $this->showSuccess('File uploaded successfully');

        // Dispatch event for parent components
        $this->dispatch('chunked-upload-completed', $fileData);
    }

    // Remove uploaded file from Livewire state
    public function removeUploadedFile(string $fileKey): void
    {
        $fileToRemove = collect($this->uploadedFiles)
            ->firstWhere('key', $fileKey);

        if (! $fileToRemove) {
            $this->showError('File not found');

            return;
        }

        $this->uploadedFiles = array_values(array_filter(
            $this->uploadedFiles,
            fn ($file) => $file['key'] !== $fileKey,
        ));

        $this->showSuccess(
            "File '{$fileToRemove['name']}' removed successfully",
        );

        // Dispatch delete event for cleanup
        $this->dispatch('chunked-upload-removed', ['key' => $fileKey]);
    }

    // Get accepted file types as comma-separated string
    public function getAcceptedTypesProperty(): string
    {
        return implode(',', $this->acceptedFileTypes);
    }

    // Get formatted file types for display
    public function getAcceptedTypesDisplayProperty(): string
    {
        $typeMap = [
            'image/jpeg' => 'JPEG',
            'image/jpg' => 'JPG',
            'image/png' => 'PNG',
            'application/pdf' => 'PDF',
            'video/mp4' => 'MP4',
            'video/avi' => 'AVI',
            'video/mov' => 'MOV',
        ];

        $displayTypes = [];
        foreach ($this->acceptedFileTypes as $type) {
            $displayTypes[] = $typeMap[$type] ?? strtoupper(
                explode('/', $type)[1] ?? 'FILE',
            );
        }

        $sizeDisplay = $this->maxFileSize >= 1048576
            ? round($this->maxFileSize / 1048576) . ' MB'
            : round($this->maxFileSize / 1024) . ' KB';

        return implode(', ', $displayTypes) . ' formats, up to ' . $sizeDisplay;
    }

    // Format file size for display
    public function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 Bytes';
        }

        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    // Enhanced upload tracking methods
    public function startUpload(array $fileData): void
    {
        $this->activeUploads[$fileData['id']] = [
            'id' => $fileData['id'],
            'name' => $fileData['name'],
            'size' => $fileData['size'],
            'type' => $fileData['type'],
            'progress' => 0,
            'status' => 'pending',
            'chunks' => [],
            'uploadedChunks' => 0,
            'totalChunks' => $fileData['totalChunks'] ?? 1,
            'startTime' => now()->toISOString(),
            'error' => null,
        ];

        $this->uploading = true;
        $this->clearMessages();
    }

    public function updateUploadProgress(string $fileId, array $updates): void
    {
        if (! isset($this->activeUploads[$fileId])) {
            return;
        }

        $this->activeUploads[$fileId] = array_merge(
            $this->activeUploads[$fileId],
            $updates,
        );

        // Calculate progress percentage
        if (
            isset($updates['uploadedChunks'])
            && $this->activeUploads[$fileId]['totalChunks'] > 1
        ) {
            $this->activeUploads[$fileId]['progress'] = round(
                (
                    $this->activeUploads[$fileId]['uploadedChunks']
                    / $this->activeUploads[$fileId]['totalChunks']
                )
                * 100,
            );
        }
    }

    public function completeUpload(string $fileId, array $fileData): void
    {
        if (isset($this->activeUploads[$fileId])) {
            $this->activeUploads[$fileId]['status'] = 'completed';
            $this->activeUploads[$fileId]['progress'] = 100;

            // Move to uploaded files
            $this->addUploadedFile([
                'key' => $fileId,
                'name' => $fileData['name'] ?? $this->activeUploads[$fileId]['name'],
                'size' => $fileData['size'] ?? $this->activeUploads[$fileId]['size'],
                'type' => $fileData['type'] ?? $this->activeUploads[$fileId]['type'],
                'url' => $fileData['url'] ?? '',
                'path' => $fileData['path'] ?? '',
                'imported' => $fileData['imported'] ?? false,
            ]);

            // Remove from active uploads after a delay
            unset($this->activeUploads[$fileId]);
        }

        $this->checkUploadingStatus();
    }

    public function failUpload(string $fileId, string $error): void
    {
        if (isset($this->activeUploads[$fileId])) {
            $this->activeUploads[$fileId]['status'] = 'error';
            $this->activeUploads[$fileId]['error'] = $error;
        }

        $this->showError($error);
        $this->checkUploadingStatus();
    }

    public function cancelUpload(string $fileId): void
    {
        if (isset($this->activeUploads[$fileId])) {
            $this->activeUploads[$fileId]['status'] = 'cancelled';
            unset($this->activeUploads[$fileId]);
        }

        $this->checkUploadingStatus();
        $this->dispatch('upload-cancelled', ['fileId' => $fileId]);
    }

    // URL Import functionality
    public function importFromUrl(string $url): void
    {
        if (empty($url) || ! $this->importUrlEndpoint) {
            $this->showError('Invalid URL or import endpoint not configured');

            return;
        }

        try {
            // This would typically make an HTTP request to import the file
            // For now, we'll simulate a successful import
            $filename = $this->extractFilenameFromUrl($url);
            $fileData = [
                'key' => uniqid(),
                'name' => $filename,
                'size' => 0, // Size would be determined by actual import
                'type' => $this->guessTypeFromUrl($url),
                'url' => $url,
                'imported' => true,
            ];

            $this->addUploadedFile($fileData);
            $this->showSuccess('File imported successfully from URL');
        } catch (Exception $e) {
            $this->showError('Failed to import from URL: ' . $e->getMessage());
        }
    }

    // Message handling
    public function showError(string $message): void
    {
        $this->error = $message;
        $this->success = null;
    }

    public function showSuccess(string $message): void
    {
        $this->success = $message;
        $this->error = null;
    }

    public function clearMessages(): void
    {
        $this->error = null;
        $this->success = null;
    }

    // Get configuration for Alpine component
    public function getComponentConfig(): array
    {
        return [
            'modalMode' => $this->modalMode,
            'allowUrlImport' => $this->allowUrlImport,
            'multiple' => $this->multiple,
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'chunkSize' => $this->chunkSize,
            'maxFiles' => $this->maxFiles,
            'maxFileSize' => $this->maxFileSize,
            'minFileSize' => $this->minFileSize,
            'maxParallelUploads' => $this->maxParallelUploads,
            'statePath' => $this->statePath,
            'routes' => [
                'store' => route('chunked-upload.store'),
                'delete' => route('chunked-upload.delete'),
                'cancel' => route('chunked-upload.cancel'),
            ],
            'storage' => [
                'disk' => $this->disk,
                'finalDir' => $this->finalDir,
                'tempDir' => $this->tempDir,
            ],
            'importUrlEndpoint' => $this->importUrlEndpoint,
            'state' => $this->uploadedFiles,
        ];
    }

    public function render()
    {
        return view('livewire.chunked-file-upload');
    }

    private function checkUploadingStatus(): void
    {
        $this->uploading = collect($this->activeUploads)->contains(function ($upload) {
            return in_array($upload['status'], ['pending', 'uploading']);
        });
    }

    // Utility methods
    private function extractFilenameFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return basename($path) ?: 'imported-file';
    }

    private function guessTypeFromUrl(string $url): string
    {
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'zip' => 'application/zip',
        ];

        return $mimeMap[$ext] ?? 'application/octet-stream';
    }
}
