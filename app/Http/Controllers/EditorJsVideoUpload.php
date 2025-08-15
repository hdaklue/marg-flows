<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ValueObjects\Dimension\AspectRatio;
use Exception;
use FFMpeg\Format\Video\X264;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Log;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

final class EditorJsVideoUpload extends Controller
{
    /**
     * Get the chunk directory path for a given file key.
     */
    protected function getChunkDirectory(string $fileKey): string
    {
        $chunkDir = config('video-upload.storage.chunk_directory', 'video-chunk-uploads');

        return storage_path("app/{$chunkDir}/{$fileKey}");
    }

    /**
     * Handle chunked video upload.
     */
    protected function handleChunkedUpload(Request $request, string $fileKey, string $fileName, int $chunkIndex, int $totalChunks): JsonResponse
    {
        $chunkFile = $request->file('video');
        $chunkDir = $this->getChunkDirectory($fileKey);

        // Create chunk directory if it doesn't exist
        if (! is_dir($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        // Store the chunk
        $chunkPath = "{$chunkDir}/chunk_{$chunkIndex}";
        $chunkFile->move($chunkDir, "chunk_{$chunkIndex}");

        Log::info('Video chunk uploaded', [
            'fileKey' => $fileKey,
            'fileName' => $fileName,
            'chunk' => $chunkIndex,
            'totalChunks' => $totalChunks,
        ]);

        // Check if all chunks are uploaded
        if ($this->allChunksUploaded($fileKey, $totalChunks)) {
            // Assemble the file
            $finalPath = $this->assembleChunkedVideoFile($fileKey, $fileName, $totalChunks);

            // Clean up chunks
            $this->cleanupChunks($fileKey);

            // Process the assembled video file
            return $this->processVideoFile($finalPath, $fileKey);
        }

        return response()->json([
            'success' => true,
            'completed' => false,
            'chunk' => $chunkIndex,
            'totalChunks' => $totalChunks,
            'message' => 'Chunk uploaded successfully',
        ]);
    }

    /**
     * Handle direct video upload (for small files).
     */
    protected function handleDirectUpload(Request $request, string $fileKey, string $fileName): JsonResponse
    {
        $uploadedFile = $request->file('video');
        $extension = $uploadedFile->getClientOriginalExtension();

        // Generate unique filename
        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;
        $videoDir = config('video-upload.storage.video_directory', 'documents/videos');

        // Store the file directly
        $path = $uploadedFile->storeAs($videoDir, $uniqueFileName, config('video-upload.storage.disk', 'public'));

        Log::info('Video uploaded directly', [
            'fileKey' => $fileKey,
            'fileName' => $fileName,
            'path' => $path,
        ]);

        // Process the uploaded video file
        return $this->processVideoFile($path, $fileKey);
    }

    /**
     * Process uploaded video file (extract metadata, generate thumbnail, etc.).
     */
    protected function processVideoFile(string $videoPath, string $fileKey): JsonResponse
    {
        try {
            $extension = pathinfo($videoPath, PATHINFO_EXTENSION);

            // Client-side conversion is now handled by ffmpeg.wasm
            // Skip server-side conversion to avoid timeouts
            $conversionResult = [
                'success' => true,
                'converted_path' => $videoPath,
                'original_path' => $videoPath,
                'conversion_performed' => false,
                'message' => 'Client-side conversion handled by browser',
            ];

            // Extract video metadata if enabled
            $videoData = [];
            if (config('video-upload.processing.extract_metadata', true)) {
                $videoData = $this->extractVideoMetadata($videoPath);
            }

            // Generate thumbnail if enabled
            $thumbnailUrl = null;
            if (config('video-upload.processing.generate_thumbnails', true)) {
                $duration = $videoData['duration'] ?? null;
                if ($duration && $duration > 0) {
                    $thumbnailPath = $this->generateVideoThumbnail($videoPath, $duration);
                    if ($thumbnailPath) {
                        $thumbnailUrl = Storage::disk(config('video-upload.storage.disk', 'public'))->url($thumbnailPath);
                    }
                }
            }

            $response = [
                'success' => true,
                'completed' => true,
                'fileKey' => $fileKey,
                'url' => Storage::disk(config('video-upload.storage.disk', 'public'))->url($videoPath),
                'file' => [
                    'url' => Storage::disk(config('video-upload.storage.disk', 'public'))->url($videoPath),
                ],
                'width' => $videoData['width'] ?? null,
                'height' => $videoData['height'] ?? null,
                'duration' => $videoData['duration'] ?? null,
                'size' => $videoData['size'] ?? null,
                'format' => strtolower($extension),
                'original_format' => $extension,
                'aspect_ratio' => $videoData['aspect_ratio'] ?? config('video-upload.processing.default_aspect_ratio', '16:9'),
                'aspect_ratio_data' => $videoData['aspect_ratio_data'] ?? null,
                'conversion' => [
                    'performed' => $conversionResult['conversion_performed'],
                    'success' => $conversionResult['success'],
                    'message' => $conversionResult['message'],
                ],
                'message' => 'Video uploaded and processed successfully',
            ];

            // Add thumbnail URL to response if available
            if ($thumbnailUrl) {
                $response['thumbnail'] = $thumbnailUrl;
                $response['file']['thumbnail'] = $thumbnailUrl;
            }

            return response()->json($response);

        } catch (Exception $e) {
            Log::error('Failed to process video file', [
                'path' => $videoPath,
                'fileKey' => $fileKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process video file. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if all chunks are uploaded.
     */
    protected function allChunksUploaded(string $fileKey, int $totalChunks): bool
    {
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
     * Assemble chunked file into final video file.
     */
    protected function assembleChunkedVideoFile(string $fileKey, string $fileName, int $totalChunks): string
    {
        $chunkDir = $this->getChunkDirectory($fileKey);

        // Generate unique filename
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;

        // Create final file path
        $videoDir = config('video-upload.storage.video_directory', 'documents/videos');
        $finalPath = $videoDir . '/' . $uniqueFileName;
        $diskName = config('video-upload.storage.disk', 'public');
        $fullFinalPath = Storage::disk($diskName)->path($finalPath);

        // Ensure directory exists
        $finalDir = dirname($fullFinalPath);
        if (! is_dir($finalDir)) {
            mkdir($finalDir, 0755, true);
        }

        // Open final file for writing
        $finalHandle = fopen($fullFinalPath, 'wb');
        throw_unless($finalHandle, new Exception('Could not create final video file'));

        try {
            // Append all chunks to final file
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = "{$chunkDir}/chunk_{$i}";

                if (! file_exists($chunkPath)) {
                    throw new Exception("Video chunk {$i} not found");
                }

                $chunkHandle = fopen($chunkPath, 'rb');
                if (! $chunkHandle) {
                    throw new Exception("Could not read video chunk {$i}");
                }

                // Copy chunk to final file
                while (! feof($chunkHandle)) {
                    $data = fread($chunkHandle, 8192);
                    fwrite($finalHandle, $data);
                }

                fclose($chunkHandle);
            }
        } finally {
            fclose($finalHandle);
        }

        Log::info('Video chunks assembled successfully', [
            'fileKey' => $fileKey,
            'fileName' => $fileName,
            'finalPath' => $finalPath,
            'totalChunks' => $totalChunks,
        ]);

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

    /**
     * Extract video metadata using Laravel FFmpeg.
     */
    private function extractVideoMetadata(string $path): array
    {
        try {
            // Get file size
            $fileSize = Storage::disk('public')->size($path);

            // Use Laravel FFmpeg to extract metadata
            $media = FFMpeg::fromDisk('public')->open($path);

            // Get duration in seconds
            $duration = $media->getDurationInSeconds();

            // Get video stream information
            $videoStream = $media->getVideoStream();
            $width = $videoStream ? $videoStream->get('width') : null;
            $height = $videoStream ? $videoStream->get('height') : null;

            // Calculate aspect ratio using the value object
            $aspectRatio = null;
            $aspectRatioString = '16:9'; // Default fallback

            if ($width && $height) {
                $aspectRatioObj = AspectRatio::from($width, $height);
                if ($aspectRatioObj) {
                    $aspectRatioString = $aspectRatioObj->getAspectRatio();
                    $aspectRatio = $aspectRatioObj->toArray();
                }
            }

            return [
                'width' => $width,
                'height' => $height,
                'duration' => $duration,
                'size' => $fileSize,
                'aspect_ratio' => $aspectRatioString,
                'aspect_ratio_data' => $aspectRatio,
            ];

        } catch (Exception $e) {
            Log::warning('Failed to extract video metadata', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            // Return basic file size if FFmpeg extraction fails
            return [
                'width' => null,
                'height' => null,
                'duration' => null,
                'size' => Storage::disk('public')->size($path),
                'aspect_ratio' => '16:9', // Default fallback
                'aspect_ratio_data' => null,
            ];
        }
    }

    /**
     * Convert video to MP4 format for better browser compatibility.
     */
    private function convertVideoToMp4(string $originalPath, string $originalExtension): array
    {
        try {
            // If already MP4, no conversion needed
            if (strtolower($originalExtension) === 'mp4') {
                return [
                    'success' => true,
                    'converted_path' => $originalPath,
                    'original_path' => $originalPath,
                    'conversion_performed' => false,
                    'message' => 'Video is already in MP4 format',
                ];
            }

            // Generate MP4 filename
            $originalFilename = pathinfo($originalPath, PATHINFO_FILENAME);
            $mp4Filename = $originalFilename . '.mp4';
            $mp4Path = 'documents/videos/' . $mp4Filename;

            Log::info('Starting video conversion to MP4', [
                'original_path' => $originalPath,
                'target_path' => $mp4Path,
                'original_extension' => $originalExtension,
            ]);

            // Convert video using Laravel FFmpeg
            $media = FFMpeg::fromDisk('public')->open($originalPath);

            // Export to MP4 with H.264 codec for better compatibility
            $media->export()
                ->toDisk('public')
                ->inFormat(new X264('libmp3lame'))
                ->save($mp4Path);

            Log::info('Video conversion to MP4 completed successfully', [
                'original_path' => $originalPath,
                'converted_path' => $mp4Path,
            ]);

            return [
                'success' => true,
                'converted_path' => $mp4Path,
                'original_path' => $originalPath,
                'conversion_performed' => true,
                'message' => 'Video successfully converted to MP4',
            ];

        } catch (Exception $e) {
            Log::error('Failed to convert video to MP4', [
                'original_path' => $originalPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return original path as fallback
            return [
                'success' => false,
                'converted_path' => $originalPath,
                'original_path' => $originalPath,
                'conversion_performed' => false,
                'message' => 'Conversion failed, using original file',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean up original file after successful conversion.
     */
    private function cleanupOriginalFile(string $originalPath, string $convertedPath): void
    {
        try {
            // Only delete if paths are different (conversion actually happened)
            if ($originalPath !== $convertedPath && Storage::disk('public')->exists($originalPath)) {
                Storage::disk('public')->delete($originalPath);

                Log::info('Original video file cleaned up after conversion', [
                    'original_path' => $originalPath,
                    'converted_path' => $convertedPath,
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Failed to cleanup original video file', [
                'original_path' => $originalPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate video thumbnail using Laravel FFmpeg.
     */
    private function generateVideoThumbnail(string $videoPath, float $duration): ?string
    {
        try {
            // Calculate thumbnail extraction time (1 second or 10% of duration if video is shorter than 10 seconds)
            $extractionTime = $duration < 10 ? ($duration * 0.1) : 1.0;

            // Generate thumbnail filename based on video filename
            $videoFilename = pathinfo($videoPath, PATHINFO_FILENAME);
            $thumbnailFilename = $videoFilename . '_thumb.jpg';
            $thumbnailPath = 'documents/video-thumbnails/' . $thumbnailFilename;

            // Ensure the thumbnail directory exists
            $thumbnailDir = 'documents/video-thumbnails';
            if (! Storage::disk('public')->exists($thumbnailDir)) {
                Storage::disk('public')->makeDirectory($thumbnailDir);
            }

            // Extract thumbnail frame using Laravel FFmpeg
            $media = FFMpeg::fromDisk('public')->open($videoPath);

            $frame = $media->getFrameFromSeconds($extractionTime);
            $frame->export()
                ->toDisk('public')
                ->save($thumbnailPath);

            Log::info('Video thumbnail generated successfully', [
                'video_path' => $videoPath,
                'thumbnail_path' => $thumbnailPath,
                'extraction_time' => $extractionTime,
            ]);

            return $thumbnailPath;

        } catch (Exception $e) {
            Log::warning('Failed to generate video thumbnail', [
                'video_path' => $videoPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Validate video file type and size.
     */
    private function validateVideoFile($file): void
    {
        $maxSize = config('video-upload.validation.max_file_size', 512000); // 500MB in KB
        $allowedMimes = config('video-upload.validation.allowed_mimes', ['mp4', 'webm', 'mov']);
        $allowedMimeTypes = config('video-upload.validation.allowed_mimetypes', ['video/mp4']);

        $validator = Validator::make(['video' => $file], [
            'video' => [
                'required',
                'file',
                'mimes:' . implode(',', $allowedMimes),
                'mimetypes:' . implode(',', $allowedMimeTypes),
                'max:' . $maxSize,
            ],
        ], [
            'video.required' => 'No file selected. Please choose a video to upload.',
            'video.mimes' => 'Invalid file format. Supported formats: ' . strtoupper(implode(', ', $allowedMimes)),
            'video.mimetypes' => 'File must be a valid video format.',
            'video.max' => 'File is too large. Maximum size allowed is ' . round($maxSize / 1024) . 'MB.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Check if filename has a valid video extension (for chunked uploads).
     */
    private function isValidVideoFileName(string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = config('video-upload.validation.allowed_mimes', [
            'mp4', 'webm', 'ogg',
        ]);

        return in_array($extension, $allowedExtensions);
    }

    /**
     * Handle the incoming video upload requests from Editor.js.
     * Supports both chunked and direct uploads.
     */
    public function __invoke(Request $request): JsonResponse
    {
        logger()->debug($request);
        $validator = Validator::make($request->all(), [
            'video' => 'required|file',
            'fileKey' => 'sometimes|string',
            'fileName' => 'sometimes|string',
            'chunk' => 'sometimes|integer|min:0',
            'chunks' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate video file type and size (skip detailed validation for chunks)
        $totalChunks = (int) $request->input('chunks', 1);
        if ($totalChunks === 1) {
            // Only validate for direct uploads, not for chunks
            try {
                $this->validateVideoFile($request->file('video'));
            } catch (ValidationException $e) {
                $firstError = collect($e->errors())->flatten()->first();

                return response()->json([
                    'success' => false,
                    'message' => $firstError,
                    'errors' => $e->errors(),
                ], 422);
            }
        } else {
            // For chunked uploads, validate the fileName extension and chunk size
            $fileName = $request->input('fileName', '');
            if (! $this->isValidVideoFileName($fileName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid video file format. Supported formats: MP4, WebM, OGG',
                ], 422);
            }

            // Validate chunk size (should be reasonable, max 50MB per chunk)
            $chunkFile = $request->file('video');
            if ($chunkFile && $chunkFile->getSize() > 50 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chunk size too large. Maximum chunk size is 50MB.',
                ], 422);
            }
        }

        try {
            $fileKey = $request->input('fileKey', uniqid() . '_' . time());
            $fileName = $request->input('fileName', $request->file('video')->getClientOriginalName());
            $chunkIndex = (int) $request->input('chunk', 0);
            $totalChunks = (int) $request->input('chunks', 1);

            if ($totalChunks > 1) {
                // Handle chunked upload
                return $this->handleChunkedUpload($request, $fileKey, $fileName, $chunkIndex, $totalChunks);
            }

            // Handle direct upload (small files)
            return $this->handleDirectUpload($request, $fileKey, $fileName);

        } catch (Exception $e) {
            Log::error('Failed to upload video', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video. Please try again.',
            ], 500);
        }
    }
}
