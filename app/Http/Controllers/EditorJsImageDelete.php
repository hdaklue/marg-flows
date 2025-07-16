<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        
        // Security check: ensure the path is within allowed directories
        if (!Str::startsWith($path, ['case-studies/', 'uploads/'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file path',
            ], 400);
        }
        
        // Additional security: prevent directory traversal
        if (Str::contains($path, ['../', '..\\'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file path',
            ], 400);
        }

        try {
            // Check if file exists before attempting to delete
            if (Storage::exists($path)) {
                Storage::delete($path);
                
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to delete EditorJS image', [
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