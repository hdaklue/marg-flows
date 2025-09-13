<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Responses\ChunkedUploadResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Log;

final class ChunkedUploadController extends Controller
{
    /**
     * Handle chunked file upload.
     */
    public function store(Request $request): JsonResponse
    {
        // File uploads require FormData (multipart/form-data), not JSON
        // The response will be JSON, but the request uses FormData for file uploads

        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'fileKey' => 'required|string',
            'name' => 'required|string',
            'chunk' => 'sometimes|integer|min:0',
            'chunks' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ChunkedUploadResponse::validationError(
                $validator->errors()->toArray(),
            );
        }

        $fileKey = $request->input('fileKey');
        $fileName = $request->input('name');
        $chunkIndex = (int) $request->input('chunk');
        $totalChunks = (int) $request->input('chunks', 1);

        try {
            if ($totalChunks > 1) {
                // Handle chunked upload
                return $this->handleChunkedUpload(
                    $request,
                    $fileKey,
                    $fileName,
                    $chunkIndex,
                    $totalChunks,
                );
            }

            // Handle direct upload
            return $this->handleDirectUpload($request, $fileKey, $fileName);
        } catch (Exception $e) {
            Log::error('Chunked upload error', [
                'message' => $e->getMessage(),
                'fileKey' => $fileKey ?? 'unknown',
                'fileName' => $fileName ?? 'unknown',
            ]);

            return ChunkedUploadResponse::error($e->getMessage());
        }
    }

    /**
     * Cancel upload and clean up chunks.
     */
    public function cancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fileKey' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ChunkedUploadResponse::validationError(
                $validator->errors()->toArray(),
            );
        }

        $fileKey = $request->input('fileKey');

        try {
            // Clean up chunk directory for this file
            $chunkDir = $this->getChunkDirectory($fileKey);
            if (is_dir($chunkDir)) {
                $files = glob("{$chunkDir}/*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($chunkDir);
            }

            return ChunkedUploadResponse::cancelled();
        } catch (Exception $e) {
            return ChunkedUploadResponse::error('Failed to cancel upload: '
            . $e->getMessage());
        }
    }

    /**
     * Delete uploaded file.
     */
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fileKey' => 'required|string',
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ChunkedUploadResponse::validationError(
                $validator->errors()->toArray(),
            );
        }

        $fileKey = $request->input('fileKey');
        $path = $request->input('path');

        $disk = config('chunked-upload.storage.disk', 'public');

        // Debug logging
        Log::info('Delete request received', [
            'fileKey' => $fileKey,
            'path' => $path,
            'disk' => $disk,
            'exists' => Storage::disk($disk)->exists($path),
        ]);

        try {
            // Delete from configured storage disk
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                Log::info('File deleted successfully', ['path' => $path, 'disk' => $disk]);
            } else {
                Log::warning('File not found for deletion', ['path' => $path, 'disk' => $disk]);
            }

            // Clean up any remaining chunk directories for this file
            $chunkDir = $this->getChunkDirectory($fileKey);
            if (is_dir($chunkDir)) {
                $files = glob("{$chunkDir}/*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($chunkDir);
            }

            return ChunkedUploadResponse::deleted();
        } catch (Exception $e) {
            return ChunkedUploadResponse::error('Failed to delete file: '
            . $e->getMessage());
        }
    }

    /**
     * Clean up old chunk directories (optional cleanup endpoint).
     */
    public function cleanup(): JsonResponse
    {
        $chunkDir = config(
            'chunked-upload.storage.chunk_directory',
            'chunk-uploads',
        );
        $chunkUploadsDir = storage_path("app/{$chunkDir}");

        if (! is_dir($chunkUploadsDir)) {
            return response()->json([
                'success' => true,
                'message' => 'No chunks to clean up',
            ]);
        }

        $cleanedCount = 0;
        $cutoffTime = time() - (24 * 60 * 60); // 24 hours ago

        $directories = glob("{$chunkUploadsDir}/*", GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $dirModTime = filemtime($dir);

            if ($dirModTime < $cutoffTime) {
                // Remove old chunk directory
                $files = glob("{$dir}/*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($dir);
                $cleanedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$cleanedCount} old chunk directories",
        ]);
    }

    /**
     * Get the chunk directory path for a given file key.
     */
    protected function getChunkDirectory(string $fileKey): string
    {
        $chunkDir = config(
            'chunked-upload.storage.chunk_directory',
            'chunk-uploads',
        );

        return storage_path("app/{$chunkDir}/{$fileKey}");
    }

    /**
     * Handle chunked file upload.
     */
    protected function handleChunkedUpload(
        Request $request,
        string $fileKey,
        string $fileName,
        int $chunkIndex,
        int $totalChunks,
    ): JsonResponse {
        $chunkFile = $request->file('file');
        $chunkDir = $this->getChunkDirectory($fileKey);

        // Create chunk directory if it doesn't exist
        if (! is_dir($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        // Store the chunk
        $chunkPath = "{$chunkDir}/chunk_{$chunkIndex}";
        $chunkFile->move($chunkDir, "chunk_{$chunkIndex}");

        // Check if all chunks are uploaded
        if ($this->allChunksUploaded($fileKey, $totalChunks)) {
            // Assemble the file
            $finalPath = $this->assembleChunkedFile(
                $fileKey,
                $fileName,
                $totalChunks,
            );

            // Clean up chunks
            $this->cleanupChunks($fileKey);

            $disk = config('chunked-upload.storage.disk', 'public');

            return ChunkedUploadResponse::assemblySuccess([
                'completed' => true,
                'fileKey' => $fileKey,
                'path' => $finalPath,
                'url' => Storage::disk($disk)->url($finalPath),
            ]);
        }

        return ChunkedUploadResponse::chunkSuccess([
            'completed' => false,
            'chunk' => $chunkIndex,
        ]);
    }

    /**
     * Handle direct file upload (for small files).
     */
    protected function handleDirectUpload(
        Request $request,
        string $fileKey,
        string $fileName,
    ): JsonResponse {
        $uploadedFile = $request->file('file');

        // Generate unique filename
        $extension = $uploadedFile->getClientOriginalExtension();
        $uniqueFileName =
            $fileKey
            . '_'
            . Str::slug(pathinfo($fileName, PATHINFO_FILENAME))
            . '.'
            . $extension;

        $disk = config('chunked-upload.storage.disk', 'public');
        $finalDirectory = config('chunked-upload.storage.final_directory', 'uploads');

        // Store the file
        $path = $uploadedFile->storeAs($finalDirectory, $uniqueFileName, $disk);

        return ChunkedUploadResponse::success([
            'completed' => true,
            'fileKey' => $fileKey,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
        ]);
    }

    /**
     * Check if all chunks are uploaded.
     */
    protected function allChunksUploaded(
        string $fileKey,
        int $totalChunks,
    ): bool {
        $chunkDir = $this->getChunkDirectory($fileKey);

        if (! is_dir($chunkDir)) {
            return false;
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDir}/chunk_{$i}";
            if (! file_exists($chunkPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assemble chunked file into final file.
     */
    protected function assembleChunkedFile(
        string $fileKey,
        string $fileName,
        int $totalChunks,
    ): string {
        $chunkDir = $this->getChunkDirectory($fileKey);
        $disk = config('chunked-upload.storage.disk', 'public');
        $finalDirectory = config('chunked-upload.storage.final_directory', 'uploads');

        // Generate unique filename
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName =
            $fileKey
            . '_'
            . Str::slug(pathinfo($fileName, PATHINFO_FILENAME))
            . '.'
            . $extension;

        // Create final file path using configured disk
        $finalPath = $finalDirectory . '/' . $uniqueFileName;
        $diskInstance = Storage::disk($disk);
        $fullFinalPath = $diskInstance->path($finalPath);

        // Ensure directory exists
        $finalDir = dirname($fullFinalPath);
        if (! is_dir($finalDir)) {
            mkdir($finalDir, 0755, true);
        }

        // Open final file for writing
        $finalHandle = fopen($fullFinalPath, 'wb');

        throw_unless(
            $finalHandle,
            new Exception('Could not create final file'),
        );

        // Append all chunks to final file
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunkDir}/chunk_{$i}";

            if (! file_exists($chunkPath)) {
                fclose($finalHandle);
                throw new Exception("Chunk {$i} not found");
            }

            $chunkHandle = fopen($chunkPath, 'rb');
            if (! $chunkHandle) {
                fclose($finalHandle);
                throw new Exception("Could not read chunk {$i}");
            }

            // Copy chunk to final file
            while (! feof($chunkHandle)) {
                $data = fread($chunkHandle, 8192);
                fwrite($finalHandle, $data);
            }

            fclose($chunkHandle);
        }

        fclose($finalHandle);

        return $finalPath;
    }

    /**
     * Clean up chunk files.
     */
    protected function cleanupChunks(string $fileKey): void
    {
        $chunkDir = $this->getChunkDirectory($fileKey);

        if (is_dir($chunkDir)) {
            // Remove all files in the directory
            $files = glob("{$chunkDir}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            // Remove the directory
            rmdir($chunkDir);
        }
    }
}
