<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Image;

use App\Services\Document\HTTP\Responses\FileDeleteResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class DeleteDocumentImage
{
    use AsAction;

    /**
     * Handle document image deletion with generalized parameters.
     */
    public function handle(string $path, ?string $disk = null): bool
    {
        try {
            $storage = $disk ? Storage::disk($disk) : Storage::getFacadeRoot();

            // Simply delete the file if it exists
            if ($storage->exists($path)) {
                $storage->delete($path);
                logger()->info('Image deleted successfully', ['path' => $path]);

                return true;
            }

            logger()->warning('Image not found for deletion', ['path' => $path]);

            return false;
        } catch (Exception $e) {
            Log::error('Failed to delete document image', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle HTTP controller request - normalize params and return JSON response.
     */
    public function asController(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $request->input('path');

        logger()->info('Delete request', ['path' => $path]);

        try {
            // Call the generalized handler
            $deleted = $this->handle($path);

            return FileDeleteResponse::success($deleted);
        } catch (Exception $e) {
            return FileDeleteResponse::error('Failed to delete file');
        }
    }
}
