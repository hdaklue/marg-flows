<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Image;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\Requests\DocumentImageUploadRequest;
use App\Services\Document\Responses\ImageUploadResponse;
use App\Services\Upload\UploadSessionManager;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

final class UploadDocumentImage
{
    use AsAction;

    /**
     * Handle document image upload with generalized parameters.
     */
    public function handle(
        Document $document,
        UploadedFile $file,
        string $tenantId,
    ): array {
        // Get organized directory from DocumentDirectoryManager
        $storageDirectory = DocumentDirectoryManager::make($document)
            ->images()
            ->getDirectory();

        // Use UploadSessionManager with http strategy for single file uploads
        $path = UploadSessionManager::start('http', $tenantId)
            ->storeIn($storageDirectory)
            ->upload($file);

        logger()->info("Saved image to: {$path}");

        // For optimization, only run for local storage
        $disk = config('directory-document.storage.disk', 'public');
        $diskDriver = Storage::disk($disk);

        // Only optimize images on local storage (cloud storage optimization requires different approach)
        if ($disk === 'public' && config('filesystems.disks.public.driver') === 'local') {
            OptimizeDocumentImage::dispatch($diskDriver->path($path));
        }

        // Extract just the filename from the full path
        $filename = basename($path);

        return [
            'path' => $path,
            'filename' => $filename,
            'document' => $document,
        ];
    }

    /**
     * Handle HTTP controller request - normalize params and return JSON response.
     */
    public function asController(
        DocumentImageUploadRequest $request,
        string $document,
    ): JsonResponse {
        try {
            // Normalize HTTP parameters to generalized ones
            $documentModel = Document::findOrFail($document);
            $file = $request->getValidatedFile();
            $tenantId = auth()->user()->getActiveTenantId();

            // Call the generalized handler
            $result = $this->handle($documentModel, $file, $tenantId);

            // Generate proper URL using document-specific serving route
            $url = route('documents.serve', [
                'document' => $document,
                'type' => 'images',
                'filename' => $result['filename'],
            ]);

            return ImageUploadResponse::success($url, $result['filename']);
        } catch (ValidationException $e) {
            // Handle validation errors
            $firstError = collect($e->errors())->flatten()->first();

            return ImageUploadResponse::validationError($firstError, $e->errors());
        } catch (Exception $e) {
            return ImageUploadResponse::error('Failed to upload image. Please try again.');
        }
    }
}
