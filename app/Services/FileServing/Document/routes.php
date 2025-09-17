<?php

declare(strict_types=1);

use App\Models\Document;
use App\Services\Document\Actions\FileServing\ServeDocumentFile;
use App\Services\Document\Actions\Image\DeleteDocumentImage;
use App\Services\Document\Actions\Image\UploadDocumentImage;
use App\Services\Document\Actions\Video\DeleteDocumentVideo;
use App\Services\Document\Actions\Video\UploadDocumentVideo;
use App\Services\FileServing\Document\DocumentFileResolver;
use Illuminate\Support\Facades\Route;

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
        Route::post('upload-image', UploadDocumentImage::class)->name(
            'documents.upload-image',
        );

        // Delete image from document
        Route::delete('delete-image', DeleteDocumentImage::class)->name('documents.delete-image');

        // Upload video to document
        Route::post('upload-video', UploadDocumentVideo::class)->name('documents.upload-video');

        // Delete video from document
        Route::delete('delete-video', DeleteDocumentVideo::class)->name('documents.delete-video');
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
Route::get('documents/{document}/serve/{type}/{filename}', ServeDocumentFile::class)
    ->where('filename', '.*')
    ->name('documents.serve');
