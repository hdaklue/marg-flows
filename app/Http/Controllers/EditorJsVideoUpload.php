<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Directory\DirectoryManager;
use App\Services\Document\Requests\DocumentVideoUploadRequest;
use App\Services\Upload\UploadSessionManager;
use App\ValueObjects\Dimension\AspectRatio;
use Exception;
use FFMpeg\Format\Video\X264;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Log;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

final class EditorJsVideoUpload extends Controller
{
    /**
     * Handle chunked video upload using UploadManager and DirectoryManager.
     */
    protected function handleChunkedUpload(
        DocumentVideoUploadRequest $request,
        string $document,
    ): JsonResponse {
        // Get tenant and session info
        $tenantId = auth()->user()->getActiveTenantId();
        $sessionId = $request->getFileKey();
        $chunkIndex = $request->getChunkIndex();
        $totalChunks = $request->getTotalChunks();
        $file = $request->file('video');

        try {
            // Configure session manager for this tenant and document-specific storage
            $sessionManager = UploadSessionManager::start(
                'http',
                $tenantId,
            )->storeIn(
                DirectoryManager::document($tenantId)
                    ->forDocument($document)
                    ->videos()
                    ->getDirectory(),
            );

            // Store the chunk
            $sessionManager->storeChunk($sessionId, $file, $chunkIndex);

            Log::info('Chunk uploaded successfully', [
                'sessionId' => $sessionId,
                'chunk' => $chunkIndex,
                'totalChunks' => $totalChunks,
            ]);

            // Check if all chunks are uploaded
            if ($sessionManager->isComplete($sessionId, $totalChunks)) {
                // Assemble all chunks into final file
                $finalPath = $sessionManager->assembleFile(
                    $sessionId,
                    $request->getFileName(),
                    $totalChunks,
                );

                // Clean up chunk files
                $sessionManager->cleanupSession($sessionId);

                // Process the completed video file
                return $this->processVideoFile($finalPath, $sessionId, $tenantId, $document);
            }

            // Return chunk upload success response
            return response()->json([
                'success' => true,
                'completed' => false,
                'chunk' => $chunkIndex,
                'totalChunks' => $totalChunks,
                'message' => 'Chunk uploaded successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Chunked upload failed', [
                'sessionId' => $sessionId,
                'chunk' => $chunkIndex,
                'totalChunks' => $totalChunks,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle direct video upload (for small files) using UploadManager and DirectoryManager.
     */
    protected function handleDirectUpload(
        DocumentVideoUploadRequest $request,
        string $document,
    ): JsonResponse {
        // Get tenant and directory configuration for direct upload
        $tenantId = auth()->user()->getActiveTenantId();
        $directory = DirectoryManager::document($tenantId)
            ->forDocument($document)
            ->videos()
            ->getDirectory();

        try {
            // Use UploadSessionManager with http strategy
            $path = UploadSessionManager::start('http', $tenantId)
                ->storeIn($directory)
                ->upload($request->file('video'));

            Log::info('Video uploaded directly', [
                'fileKey' => $request->getFileKey(),
                'fileName' => $request->getFileName(),
                'path' => $path,
            ]);

            // Process the uploaded video file
            return $this->processVideoFile($path, $request->getFileKey(), $tenantId, $document);
        } catch (Exception $e) {
            Log::error('Direct upload failed', [
                'fileKey' => $request->getFileKey(),
                'fileName' => $request->getFileName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process uploaded video file (extract metadata, generate thumbnail, etc.).
     */
    protected function processVideoFile(
        string $videoPath,
        string $fileKey,
        string $tenantId,
        string $documentId,
    ): JsonResponse {
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
                try {
                    $videoData = $this->extractVideoMetadata($videoPath);
                } catch (Exception $e) {
                    Log::warning('Failed to extract video metadata', [
                        'path' => $videoPath,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue without metadata - not critical for video upload success
                    $videoData = [];
                }
            }

            // Generate thumbnail if enabled
            $thumbnailUrl = null;
            $thumbnailPath = null;
            if (config('video-upload.processing.generate_thumbnails', true)) {
                $duration = $videoData['duration'] ?? null;
                if ($duration && $duration > 0) {
                    try {
                        $thumbnailPath = $this->generateVideoThumbnail(
                            $videoPath,
                            $duration,
                            $tenantId,
                            $documentId,
                        );
                        if ($thumbnailPath) {
                            $disk = config('chunked-upload.storage.disk', 'public');
                            $thumbnailUrl = Storage::disk($disk)->url($thumbnailPath);
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to generate video thumbnail', [
                            'path' => $videoPath,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue without thumbnail - not critical for video upload success
                        $thumbnailPath = null;
                    }
                }
            }

            $disk = config('chunked-upload.storage.disk', 'public');

            // Extract just the filename from the full path for frontend resolution
            $filename = basename($videoPath);

            $response = [
                'success' => true,
                'completed' => true,
                'fileKey' => $fileKey,
                'file' => [
                    'filename' => $filename,
                ],
                'width' => $videoData['width'] ?? null,
                'height' => $videoData['height'] ?? null,
                'duration' => $videoData['duration'] ?? null,
                'size' => $videoData['size'] ?? null,
                'format' => strtolower($extension),
                'original_format' => $extension,
                'aspect_ratio' => $videoData['aspect_ratio'] ?? config(
                    'video-upload.processing.default_aspect_ratio',
                    '16:9',
                ),
                'aspect_ratio_data' => $videoData['aspect_ratio_data'] ?? null,
                'conversion' => [
                    'performed' => $conversionResult['conversion_performed'],
                    'success' => $conversionResult['success'],
                    'message' => $conversionResult['message'],
                ],
                'message' => 'Video uploaded and processed successfully',
            ];

            // Add thumbnail filename to response if available
            if ($thumbnailPath) {
                $thumbnailFilename = basename($thumbnailPath);
                $response['file']['thumbnail'] = $thumbnailFilename;
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
     * Extract video metadata using Laravel FFmpeg.
     */
    private function extractVideoMetadata(string $path): array
    {
        try {
            $disk = config('chunked-upload.storage.disk', 'public');

            // Get file size
            $fileSize = Storage::disk($disk)->size($path);

            // Use Laravel FFmpeg to extract metadata
            $media = FFMpeg::fromDisk($disk)->open($path);

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
            $disk = config('chunked-upload.storage.disk', 'public');

            return [
                'width' => null,
                'height' => null,
                'duration' => null,
                'size' => Storage::disk($disk)->size($path),
                'aspect_ratio' => '16:9', // Default fallback
                'aspect_ratio_data' => null,
            ];
        }
    }

    /**
     * Convert video to MP4 format for better browser compatibility.
     */
    private function convertVideoToMp4(
        string $originalPath,
        string $originalExtension,
    ): array {
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

            $disk = config('chunked-upload.storage.disk', 'public');

            // Convert video using Laravel FFmpeg
            $media = FFMpeg::fromDisk($disk)->open($originalPath);

            // Export to MP4 with H.264 codec for better compatibility
            $media
                ->export()
                ->toDisk($disk)
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
    private function cleanupOriginalFile(
        string $originalPath,
        string $convertedPath,
    ): void {
        try {
            $disk = config('chunked-upload.storage.disk', 'public');

            // Only delete if paths are different (conversion actually happened)
            if (
                $originalPath !== $convertedPath
                && Storage::disk($disk)->exists($originalPath)
            ) {
                Storage::disk($disk)->delete($originalPath);

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
    private function generateVideoThumbnail(
        string $videoPath,
        float $duration,
        string $tenantId,
        string $documentId,
    ): ?string {
        try {
            // Calculate thumbnail extraction time (1 second or 10% of duration if video is shorter than 10 seconds)
            $extractionTime = $duration < 10 ? $duration * 0.1 : 1.0;

            // Generate thumbnail filename based on video filename
            $videoFilename = pathinfo($videoPath, PATHINFO_FILENAME);
            $thumbnailFilename = $videoFilename . '_thumb.jpg';

            // Use the proper thumbnail directory structure: {tenant}/documents/{documentId}/videos/prev/
            $thumbnailStrategy = DirectoryManager::document($tenantId)
                ->forDocument($documentId)
                ->videos()
                ->asThumbnails();

            $thumbnailPath = $thumbnailStrategy->getDirectory() . '/' . $thumbnailFilename;

            $disk = config('chunked-upload.storage.disk', 'public');

            // Ensure the thumbnail directory exists
            Storage::disk($disk)->makeDirectory($thumbnailStrategy->getDirectory());

            // Extract thumbnail frame using Laravel FFmpeg
            $media = FFMpeg::fromDisk($disk)->open($videoPath);

            $frame = $media->getFrameFromSeconds($extractionTime);
            $frame->export()->toDisk($disk)->save($thumbnailPath);

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
        $allowedMimes = config('video-upload.validation.allowed_mimes', [
            'mp4',
            'webm',
            'mov',
        ]);
        $allowedMimeTypes = config('video-upload.validation.allowed_mimetypes', [
            'video/mp4',
        ]);

        $validator = Validator::make(
            ['video' => $file],
            [
                'video' => [
                    'required',
                    'file',
                    'mimes:' . implode(',', $allowedMimes),
                    'mimetypes:' . implode(',', $allowedMimeTypes),
                    'max:' . $maxSize,
                ],
            ],
            [
                'video.required' => 'No file selected. Please choose a video to upload.',
                'video.mimes' => 'Invalid file format. Supported formats: '
                    . strtoupper(implode(', ', $allowedMimes)),
                'video.mimetypes' => 'File must be a valid video format.',
                'video.max' => 'File is too large. Maximum size allowed is '
                    . round($maxSize / 1024)
                    . 'MB.',
            ],
        );

        throw_if($validator->fails(), new ValidationException($validator));
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
            'mp4',
            'webm',
            'ogg',
        ]);

        return in_array($extension, $allowedExtensions);
    }

    /**
     * Handle the incoming video upload requests from Editor.js.
     * Supports both chunked and direct uploads using new architecture.
     */
    public function __invoke(
        DocumentVideoUploadRequest $request,
        string $document,
    ): JsonResponse {
        try {
            if ($request->isChunkedUpload()) {
                return $this->handleChunkedUpload($request, $document);
            }

            return $this->handleDirectUpload($request, $document);
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
