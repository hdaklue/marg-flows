<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentImageUploadController;
use App\Http\Controllers\EditorJsImageDelete;
use App\Http\Controllers\EditorJsVideoDelete;
use App\Http\Controllers\EditorJsVideoUpload;
use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\FileServing\Document\DocumentFileResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
 |--------------------------------------------------------------------------
 | Document File Serving Routes
 |--------------------------------------------------------------------------
 |
 | Routes for document-related file operations including upload, delete,
 | and serving of images, videos, and document attachments. All routes
 | require authentication and proper document permissions.
 |
 */

// Document image operations
Route::prefix('documents/{document}')
    ->middleware(['auth'])
    ->group(function () {
        // Upload image to document
        Route::post('upload-image', DocumentImageUploadController::class)->name(
            'documents.upload-image',
        );

        // Delete image from document
        Route::delete('delete-image', EditorJsImageDelete::class)->name('documents.delete-image');

        // Upload video to document
        Route::post('upload-video', EditorJsVideoUpload::class)->name('documents.upload-video');

        // Delete video from document
        Route::delete('delete-video', EditorJsVideoDelete::class)->name('documents.delete-video');
    });

// Document file serving routes
Route::prefix('documents/{document}/files')
    ->middleware(['auth'])
    ->group(function () {
        // Get secure URL for document file
        Route::get('{type}/{filename}/url', function (
            string $document,
            string $type,
            string $filename,
        ) {
            $documentModel = Document::findOrFail($document);
            $resolver = DocumentFileResolver::make($documentModel);

            return response()->json([
                'url' => $resolver->resolveSecureUrl($documentModel, $type, $filename),
            ]);
        })->name('documents.files.secure-url');

        // Get temporary URL for document file
        Route::post('{type}/{filename}/temp-url', function (
            string $document,
            string $type,
            string $filename,
        ) {
            $documentModel = Document::findOrFail($document);
            $resolver = DocumentFileResolver::make($documentModel);

            $expires = request()->integer('expires', 3600); // Default 1 hour

            return response()->json([
                'url' => $resolver->resolveTemporaryUrl($documentModel, $type, $filename, $expires),
            ]);
        })->name('documents.files.temp-url');

        // Check if file exists
        Route::get('{type}/{filename}/exists', function (
            string $document,
            string $type,
            string $filename,
        ) {
            $documentModel = Document::findOrFail($document);
            $resolver = DocumentFileResolver::make($documentModel);

            return response()->json([
                'exists' => $resolver->fileExists($documentModel, $type, $filename),
            ]);
        })->name('documents.files.exists');

        // Get file info (size, exists, etc.)
        Route::get('{type}/{filename}/info', function (
            string $document,
            string $type,
            string $filename,
        ) {
            $documentModel = Document::findOrFail($document);
            $resolver = DocumentFileResolver::make($documentModel);

            return response()->json([
                'exists' => $resolver->fileExists($documentModel, $type, $filename),
                'size' => $resolver->getFileSize($documentModel, $type, $filename),
                'filename' => $filename,
                'type' => $type,
            ]);
        })->name('documents.files.info');

        // Delete document file
        Route::delete('{type}/{filename}', function (
            string $document,
            string $type,
            string $filename,
        ) {
            $documentModel = Document::findOrFail($document);
            $resolver = DocumentFileResolver::make($documentModel);

            $deleted = $resolver->deleteFile($documentModel, $type, $filename);

            return response()->json([
                'deleted' => $deleted,
            ]);
        })->name('documents.files.delete');

        // List all files of a specific type for document
        Route::get('{type}', function (string $document, string $type) {
            $documentModel = Document::findOrFail($document);
            $resolver = DocumentFileResolver::make($documentModel);

            return response()->json([
                'files' => $resolver->getDocumentFiles($documentModel, $type),
            ]);
        })->name('documents.files.list');
    });

// Document file serving (actual file delivery)
Route::get('documents/{document}/serve/{type}/{filename}', function (
    string $document,
    string $type,
    string $filename,
) {
    $documentModel = Document::findOrFail($document);
    $resolver = DocumentFileResolver::make($documentModel);

    // Validate access
    if (! auth()->user() || ! $resolver->validateAccess($documentModel)) {
        abort(403, 'Access denied');
    }

    // Get file path using DocumentDirectoryManager
    $directoryManager = DocumentDirectoryManager::make($documentModel);

    // Get the specific directory for the file type
    $directory = match ($type) {
        'images' => $directoryManager->images()->getDirectory(),
        'videos' => $directoryManager->videos()->getDirectory(),
        default => throw new \InvalidArgumentException("Unsupported file type: {$type}"),
    };
    $disk = config('directory-document.storage.disk', 'public');
    $path = $directory . "/{$filename}";

    // Log detailed debugging information
    Log::info('Document serve request', [
        'document_id' => $document,
        'tenant_id' => $documentModel->getTenant()->getKey(),
        'type' => $type,
        'filename' => $filename,
        'directory' => $directory,
        'disk' => $disk,
        'full_path' => $path,
        'user_id' => auth()->id(),
        'request_url' => request()->fullUrl(),
    ]);

    if (! Storage::disk($disk)->exists($path)) {
        // Log available files for debugging
        $availableFiles = Storage::disk($disk)->exists($directory)
            ? Storage::disk($disk)->files($directory)
            : [];

        Log::error('File not found', [
            'requested_path' => $path,
            'directory_exists' => Storage::disk($disk)->exists($directory),
            'available_files' => $availableFiles,
            'disk' => $disk,
        ]);
        abort(404, 'File not found');
    }

    // Get file info for proper headers
    $size = Storage::disk($disk)->size($path);
    $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
    
    // Create streaming response with proper headers for video playback
    $response = Storage::disk($disk)->response($path, basename($path), [
        'Content-Type' => $mimeType,
        'Content-Length' => $size,
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, max-age=3600',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => 'Range, If-Range',
    ]);

    return $response;
})
    ->where('filename', '.*')
    ->middleware(['auth'])
    ->name('documents.serve');
