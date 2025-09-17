<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\ValueObjects\Dimension\AspectRatio;
use Exception;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

final class ExtractVideoMetadata
{
    use AsAction;

    /**
     * Extract video metadata using Laravel FFmpeg.
     */
    public function handle(string $path): array
    {
        try {
            $disk = config('chunked-upload.storage.disk', 'public');
            $fileSize = Storage::disk($disk)->size($path);
            $media = FFMpeg::fromDisk($disk)->open($path);
            $duration = $media->getDurationInSeconds();
            $videoStream = $media->getVideoStream();
            $width = $videoStream ? $videoStream->get('width') : null;
            $height = $videoStream ? $videoStream->get('height') : null;

            $aspectRatio = null;
            $aspectRatioString = '16:9';

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

            $disk = config('chunked-upload.storage.disk', 'public');

            return [
                'width' => null,
                'height' => null,
                'duration' => null,
                'size' => Storage::disk($disk)->size($path),
                'aspect_ratio' => '16:9',
                'aspect_ratio_data' => null,
            ];
        }
    }
}
