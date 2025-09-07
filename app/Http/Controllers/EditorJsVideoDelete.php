<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;

final class EditorJsVideoDelete extends Controller
{
    /**
     * Handle the incoming video delete requests from Editor.js.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $request->input('path');

        logger()->info('Video delete request', ['path' => $path]);

        try {
            // Convert URL path to storage path if needed
            $storagePath = $this->convertUrlToStoragePath($path);

            // Simply delete the file if it exists
            if (Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
                logger()->info('Video file deleted successfully', [
                    'path' => $storagePath,
                ]);
            } else {
                logger()->warning('Video file not found for deletion', [
                    'path' => $storagePath,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete EditorJS video', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video',
            ], 500);
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
        if (!str_starts_with($path, 'documents/videos/')) {
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
