<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Services\Document\Responses\FileDeleteResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class DeleteDocumentVideo
{
    use AsAction;

    /**
     * Handle document video deletion with generalized parameters.
     */
    public function handle(string $path, ?string $disk = null): bool
    {
        try {
            // Convert URL path to storage path if needed
            $storagePath = $this->convertUrlToStoragePath($path);
            $storage = $disk ? Storage::disk($disk) : Storage::disk('public');

            // Simply delete the file if it exists
            if ($storage->exists($storagePath)) {
                $storage->delete($storagePath);
                logger()->info('Video file deleted successfully', [
                    'path' => $storagePath,
                ]);

                return true;
            }

            logger()->warning('Video file not found for deletion', [
                'path' => $storagePath,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Failed to delete document video', [
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

        logger()->info('Video delete request', ['path' => $path]);

        try {
            // Call the generalized handler
            $deleted = $this->handle($path);

            return FileDeleteResponse::success(
                $deleted,
                $deleted ? 'Video deleted successfully' : 'Video file not found',
            );
        } catch (Exception $e) {
            return FileDeleteResponse::error('Failed to delete video');
        }
    }

    /**
     * Convert URL path to storage path for deletion.
     */
    private function convertUrlToStoragePath(string $path): string
    {
        // Remove storage URL prefix if present
        $path = str_replace('/storage/', '', $path);

        // Ensure it starts with documents/videos/
        if (! str_starts_with($path, 'documents/videos/')) {
            // If path doesn't contain documents/videos, assume it's just the filename
            if (str_contains($path, '/')) {
                $filename = basename($path);
                $path = 'documents/videos/' . $filename;
            } else {
                $path = 'documents/videos/' . $path;
            }
        }

        return $path;
    }
}
