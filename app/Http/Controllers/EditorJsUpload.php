<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EditorJs\OptimizeEditorJsImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class EditorJsUpload extends Controller
{
    /**
     * Handle the imcoming image upload requests from Editor.js.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => ['required', 'mimes:jpeg,jpg,png,gif,webp,avif,heic,tiff', 'image', 'max:1024'],
            ], [
                'image.required' => 'No file selected. Please choose an image to upload.',
                'image.mimes' => 'Invalid file format. Supported formats: JPEG, PNG, GIF, WebP, AVIF, HEIC, TIFF',
                'image.image' => 'File must be a valid image.',
                'image.max' => 'File is too large. Maximum size allowed is 1MB.',
            ]);
        } catch (ValidationException $e) {
            // Get the first error message for user-friendly display
            $firstError = collect($e->errors())->flatten()->first();
            
            return response()->json([
                'success' => 0,
                'message' => $firstError,
                'errors' => $e->errors(),
            ], 422);
        }

        $extension = $request->image->extension();
        $uniqueFilename = uniqid() . '_' . time() . '.' . $extension;
        $path = $request->image->storeAs('documents', $uniqueFilename);

        logger()->info('saving unique file: ' . $uniqueFilename . ' to: ' . $path);

        OptimizeEditorJsImage::dispatch(Storage::path($path));

        return response()->json([
            'success' => 1,
            'url' => Storage::url($path),
            'file' => [
                'url' => Storage::url($path),
            ],
        ]);
    }

}
