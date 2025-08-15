<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EditorJs\OptimizeEditorJsImage;
use App\Services\Directory\Facades\DirectoryManager;
use App\Services\Document\Requests\DocumentImageUploadRequest;
use App\Services\Upload\Facades\UploadManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class DocumentImageUploadController extends Controller
{
    /**
     * Handle document image upload requests with tenant-aware storage.
     */
    public function __invoke(DocumentImageUploadRequest $request, string $document): JsonResponse
    {
        try {
            // Validation is automatically handled by DocumentImageUploadRequest
            $file = $request->getValidatedFile();

            // Get organized directory from DirectoryManager
            $storageDirectory = DirectoryManager::document()
                ->forTenant(auth()->user()->getActiveTenantId())
                ->forDocument($document)
                ->images()
                ->getDirectory();

            // Use UploadManager with simple strategy for single file uploads
            $path = UploadManager::simple()
                ->forTenant(auth()->user()->getActiveTenantId())
                ->storeIn($storageDirectory)
                ->upload($file);

            // Get URL using Storage facade
            $url = Storage::url($path);

            logger()->info("Saved image to: {$path}");

            OptimizeEditorJsImage::dispatch(Storage::path($path));

            return response()->json([
                'success' => 1,
                'url' => $url,
                'file' => [
                    'url' => $url,
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
