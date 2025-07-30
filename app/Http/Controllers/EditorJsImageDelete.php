<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;

final class EditorJsImageDelete extends Controller
{
    /**
     * Handle the incoming image delete requests from Editor.js.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $request->input('path');

        logger()->info('Delete request', ['path' => $path]);

        try {
            // Simply delete the file if it exists
            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete EditorJS image', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
            ], 500);
        }
    }
}
