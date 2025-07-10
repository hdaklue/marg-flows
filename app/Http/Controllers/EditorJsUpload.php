<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EditorJs\OptimizeEditorJsImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class EditorJsUpload extends Controller
{
    /**
     * Handle the imcoming image upload requests from Editor.js.
     */
    public function __invoke(Request $request): JsonResponse
    {

        $request->validate([
            'image' => ['required', 'mimes:jpg,png,jpeg,webm', 'image', 'max:3072'],
        ]);

        $extension = $request->image->extension();

        $path = $request->image->storeAs('case-studies', str()->random() . '.' . $extension);

        OptimizeEditorJsImage::dispatch(Storage::path($path));

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => Storage::url($path),
            ],
        ]);
    }
}
