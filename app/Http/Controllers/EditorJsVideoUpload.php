<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Log;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use App\ValueObjects\Image\AspectRatio;

final class EditorJsVideoUpload extends Controller
{
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
            if (!Storage::disk('public')->exists($thumbnailDir)) {
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
     * Handle the incoming video upload requests from Editor.js.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'video' => [
                    'required',
                    'mimes:mp4,webm,mov,avi,mkv,wmv,flv,m4v,3gp,ogg',
                    'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska,video/x-ms-wmv,video/x-flv,video/x-m4v,video/3gpp,video/ogg',
                    'max:102400', // 100MB max
                ],
            ], [
                'video.required' => 'No file selected. Please choose a video to upload.',
                'video.mimes' => 'Invalid file format. Supported formats: MP4, WebM, MOV, AVI, MKV, WMV, FLV, M4V, 3GP, OGG',
                'video.mimetypes' => 'File must be a valid video format.',
                'video.max' => 'File is too large. Maximum size allowed is 100MB.',
            ]);
        } catch (ValidationException $e) {
            // Get the first error message for user-friendly display
            $firstError = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $extension = $request->video->extension();
            $uniqueFilename = uniqid() . '_' . time() . '.' . $extension;
            $path = $request->video->storeAs('documents/videos', $uniqueFilename, 'public');

            logger()->info('saving unique video file: ' . $uniqueFilename . ' to: ' . $path);

            // Extract video metadata using Laravel FFmpeg
            $videoData = $this->extractVideoMetadata($path);

            // Generate thumbnail if we have video duration
            $thumbnailUrl = null;
            $duration = $videoData['duration'] ?? null;
            
            if ($duration && $duration > 0) {
                $thumbnailPath = $this->generateVideoThumbnail($path, $duration);
                if ($thumbnailPath) {
                    $thumbnailUrl = Storage::url($thumbnailPath);
                }
            }

            $response = [
                'success' => true,
                'url' => Storage::url($path),
                'file' => [
                    'url' => Storage::url($path),
                ],
                'width' => $videoData['width'] ?? null,
                'height' => $videoData['height'] ?? null,
                'duration' => $videoData['duration'] ?? null,
                'size' => $videoData['size'] ?? null,
                'format' => $extension,
                'aspect_ratio' => $videoData['aspect_ratio'] ?? '16:9',
                'aspect_ratio_data' => $videoData['aspect_ratio_data'] ?? null,
            ];

            // Add thumbnail URL to response if available
            if ($thumbnailUrl) {
                $response['thumbnail'] = $thumbnailUrl;
                $response['file']['thumbnail'] = $thumbnailUrl;
            }

            return response()->json($response);

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
