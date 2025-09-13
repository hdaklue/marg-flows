<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EditorJs\OptimizeEditorJsImage;
use App\Services\Directory\DirectoryManager;
use App\Services\Document\Requests\DocumentImageUploadRequest;
use App\Services\Upload\UploadSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class DocumentImageUploadController extends Controller
{
    /**
     * Handle document image upload requests with tenant-aware storage.
     */
    public function __invoke(
        DocumentImageUploadRequest $request,
        string $document,
    ): JsonResponse {
        try {
            // Validation is automatically handled by DocumentImageUploadRequest
            $file = $request->getValidatedFile();

            // Get organized directory from DirectoryManager
            $storageDirectory = DirectoryManager::document(
                auth()->user()->getActiveTenantId(),
            )
                ->forDocument($document)
                ->images()
                ->getDirectory();

            // Use UploadSessionManager with http strategy for single file uploads
            $path = UploadSessionManager::start(
                'http',
                auth()->user()->getActiveTenantId(),
            )
                ->storeIn($storageDirectory)
                ->upload($file);

            logger()->info("Saved image to: {$path}");

            // For optimization, only run for local storage
            $disk = config('document.storage.disk', 'public');
            $diskDriver = Storage::disk($disk);

            // Only optimize images on local storage (cloud storage optimization requires different approach)
            if ($disk === 'public' && config('filesystems.disks.public.driver') === 'local') {
                OptimizeEditorJsImage::dispatch($diskDriver->path($path));
            }

            // Extract just the filename from the full path
            // The frontend will resolve URLs dynamically using DirectoryManager
            $filename = basename($path);

            return response()->json([
                'success' => 1,
                'file' => [
                    'filename' => $filename,
                ],
            ]);
        } catch (ValidationException $e) {
            // Handle validation errors
            $firstError = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => 0,
                'message' => $firstError,
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
