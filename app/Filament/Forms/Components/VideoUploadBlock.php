<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;

final class VideoUploadBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'video-upload';
    }

    public static function getLabel(): string
    {
        return 'Video Upload';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-video-camera';
    }

    public static function isEditable(): bool
    {
        return true;
    }

    public static function getEditAction(): ?string
    {
        return 'edit';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Upload Video')
            ->modalWidth('2xl')
            ->schema([
                FileUpload::make('video')
                    ->label('Video File')
                    ->acceptedFileTypes([
                        'video/mp4',
                        'video/webm',
                        'video/ogg',
                        'video/quicktime',
                        'video/x-msvideo',
                    ])
                    ->maxSize(50 * 1024) // 50MB max
                    ->directory('documents/videos')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required()
                    ->helperText('Supported formats: MP4, WebM, OGG, MOV, AVI. Maximum size: 50MB.')
                    ->extraAttributes([
                        'accept' => 'video/*',
                    ]),

                TextInput::make('caption')
                    ->label('Caption')
                    ->placeholder('Enter video caption...')
                    ->maxLength(255),

                TextInput::make('width')
                    ->label('Display Width (optional)')
                    ->placeholder('e.g., 640')
                    ->numeric()
                    ->helperText('Leave empty for responsive width'),

                TextInput::make('height')
                    ->label('Display Height (optional)')
                    ->placeholder('e.g., 360')
                    ->numeric()
                    ->helperText('Leave empty for auto height'),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        // Process the raw form data if it contains a file path instead of URL
        if (isset($config['video']) && !isset($config['url'])) {
            $videoPath = $config['video'];
            $videoUrl = Storage::url($videoPath);
            
            // Get video metadata
            $fullPath = Storage::path($videoPath);
            $metadata = static::extractVideoMetadata($fullPath);
            
            // Generate thumbnail if possible
            $thumbnailUrl = static::generateVideoThumbnail($fullPath, $videoPath);
            
            // Transform the config to the expected format
            $config = [
                'url' => $videoUrl,
                'path' => $videoPath,
                'caption' => $config['caption'] ?? '',
                'width' => $config['width'] ?? null,
                'height' => $config['height'] ?? null,
                'thumbnail' => $thumbnailUrl,
                'duration' => $metadata['duration'] ?? null,
                'format' => $metadata['format'] ?? null,
                'size' => $metadata['size'] ?? null,
                'aspect_ratio' => $metadata['aspect_ratio'] ?? '16:9',
            ];
        }

        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.video-upload.preview', [
            'url' => $config['url'] ?? null,
            'thumbnail' => $config['thumbnail'] ?? null,
            'caption' => $config['caption'] ?? null,
        ])->render();
    }

    public static function toHtml(array $config, array $data): ?string
    {
        // Process the raw form data if it contains a file path instead of URL
        if (isset($config['video']) && !isset($config['url'])) {
            $videoPath = $config['video'];
            $videoUrl = Storage::url($videoPath);
            
            // Get video metadata
            $fullPath = Storage::path($videoPath);
            $metadata = static::extractVideoMetadata($fullPath);
            
            // Generate thumbnail if possible
            $thumbnailUrl = static::generateVideoThumbnail($fullPath, $videoPath);
            
            // Transform the config to the expected format
            $config = [
                'url' => $videoUrl,
                'path' => $videoPath,
                'caption' => $config['caption'] ?? '',
                'width' => $config['width'] ?? null,
                'height' => $config['height'] ?? null,
                'thumbnail' => $thumbnailUrl,
                'duration' => $metadata['duration'] ?? null,
                'format' => $metadata['format'] ?? null,
                'size' => $metadata['size'] ?? null,
                'aspect_ratio' => $metadata['aspect_ratio'] ?? '16:9',
            ];
        }

        if (empty($config['url'])) {
            return '';
        }

        $videoUrl = $config['url'];
        $caption = $config['caption'] ?? null;
        $width = ! empty($config['width']) ? (int) $config['width'] : null;
        $height = ! empty($config['height']) ? (int) $config['height'] : null;

        // Generate unique ID for Video.js
        $videoId = 'video-' . Str::random(8);

        // Build video attributes
        $videoAttributes = [
            'id' => $videoId,
            'class' => 'video-js vjs-default-skin',
            'controls' => true,
            'preload' => 'metadata',
            'data-setup' => '{}',
        ];

        if ($width && $height) {
            $videoAttributes['width'] = $width;
            $videoAttributes['height'] = $height;
        } else {
            $videoAttributes['class'] .= ' vjs-fluid';
        }

        // Build poster attribute if thumbnail exists
        if (! empty($config['thumbnail'])) {
            $videoAttributes['poster'] = $config['thumbnail'];
        }

        // Convert attributes to string
        $attributesString = '';
        foreach ($videoAttributes as $key => $value) {
            if (is_bool($value)) {
                $attributesString .= $value ? " {$key}" : '';
            } else {
                $attributesString .= " {$key}=\"" . htmlspecialchars($value) . '"';
            }
        }

        // Container styling
        $containerClass = 'video-upload-block my-4';
        if (! $width || ! $height) {
            $containerClass .= ' aspect-video';
        }

        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.video-upload.index', [
            'url' => $videoUrl,
            'caption' => $caption,
            'containerClass' => $containerClass,
            'attributesString' => $attributesString,
            'mimeType' => self::getVideoMimeType($videoUrl),
            'videoId' => $videoId,
            'fluid' => ! $width || ! $height,
        ])->render();
    }

    protected static function extractVideoMetadata(string $filePath): array
    {
        $metadata = [];

        try {
            if (file_exists($filePath)) {
                $metadata['size'] = filesize($filePath);

                // Try to get video info using getimagesize (works for some video formats)
                $info = @getimagesize($filePath);
                if ($info) {
                    $metadata['width'] = $info[0];
                    $metadata['height'] = $info[1];
                    $metadata['aspect_ratio'] = $info[0] . ':' . $info[1];

                    // Simplify common aspect ratios
                    $gcd = self::gcd($info[0], $info[1]);
                    $simplifiedWidth = $info[0] / $gcd;
                    $simplifiedHeight = $info[1] / $gcd;
                    $metadata['aspect_ratio'] = $simplifiedWidth . ':' . $simplifiedHeight;
                }

                // Get file extension for format
                $metadata['format'] = strtoupper(pathinfo($filePath, PATHINFO_EXTENSION));
            }
        } catch (Exception $e) {
            Log::warning('Failed to extract video metadata: ' . $e->getMessage());
        }

        return $metadata;
    }

    protected static function generateVideoThumbnail(string $videoPath, string $storagePath): ?string
    {
        try {
            // Only generate thumbnail if ffmpeg is available (optional)
            if (! function_exists('exec')) {
                return null;
            }

            $thumbnailPath = str_replace(['.mp4', '.webm', '.ogg', '.mov', '.avi'], '_thumb.jpg', $storagePath);
            $thumbnailFullPath = Storage::path($thumbnailPath);

            // Create directory if it doesn't exist
            $thumbnailDir = dirname($thumbnailFullPath);
            if (! is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Try to generate thumbnail using ffmpeg (if available)
            $command = 'ffmpeg -i ' . escapeshellarg($videoPath) . ' -ss 00:00:01.000 -vframes 1 -f image2 ' . escapeshellarg($thumbnailFullPath) . ' 2>/dev/null';
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($thumbnailFullPath)) {
                return Storage::url($thumbnailPath);
            }
        } catch (Exception $e) {
            Log::info('Thumbnail generation skipped: ' . $e->getMessage());
        }

        return null;
    }

    public static function getVideoMimeType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogv' => 'video/ogg',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'mkv' => 'video/x-matroska',
            'm4v' => 'video/mp4',
        ];

        return $mimeTypes[$extension] ?? 'video/mp4';
    }

    protected static function gcd(int $a, int $b): int
    {
        return $b ? self::gcd($b, $a % $b) : $a;
    }
}
