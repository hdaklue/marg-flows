<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Services\Avatar\AvatarService;
use App\Services\Directory\Managers\SystemDirectoryManager;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Image\Image;

final class GenerateUserAvatar
{
    use AsAction;

    public function handle(string $url, User $user)
    {
        // Log the URL to debug what we're downloading
        logger()->info("Downloading avatar from URL: {$url}");

        // Download image using Laravel's HTTP client with timeout
        $response = Http::timeout(30)->get($url);

        throw_if($response->failed(), new Exception("Failed to download image from URL: {$url}"));

        // Detect file extension from content type or URL
        $extension = $this->detectExtension($response, $url);
        $fileName = $this->generateFileName($user, $extension);

        // Validate that we got image content
        throw_if(empty($response->body()), new Exception("Empty response body from URL: {$url}"));

        // Process content based on file type
        $content = $response->body();
        if ($extension === 'svg') {
            $content = $this->sanitizeAndValidateSvg($content);
        }

        $file = UploadedFile::fake()->createWithContent($fileName, $content);
        $avatarFileName = SystemDirectoryManager::instance()->avatars()->store($file);
        $user->updateProfileAvatar($avatarFileName);
    }

    private function generateFileName(User $user, string $extension = 'svg'): string
    {
        return AvatarService::generateFileName($user) . '.' . $extension;
    }

    private function detectExtension($response, string $url): string
    {
        // Try to get extension from Content-Type header
        $contentType = $response->header('Content-Type');
        if ($contentType) {
            $extension = match (true) {
                str_contains($contentType, 'image/svg+xml')
                    || str_contains($contentType, 'image/svg') => 'svg',
                str_contains($contentType, 'image/png') => 'png',
                str_contains($contentType, 'image/jpeg') || str_contains($contentType, 'image/jpg') => 'jpg',
                str_contains($contentType, 'image/gif') => 'gif',
                str_contains($contentType, 'image/webp') => 'webp',
                default => null,
            };

            if ($extension) {
                return $extension;
            }
        }

        // Fallback: try to detect from URL
        $urlExtension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (in_array(strtolower($urlExtension), [
            'svg',
            'png',
            'jpg',
            'jpeg',
            'gif',
            'webp',
        ])) {
            return strtolower($urlExtension);
        }

        // Default to svg for ui-avatars.com URLs
        return 'svg';
    }

    private function sanitizeAndValidateSvg(string $content): string
    {
        // Remove any potential BOM, extra whitespace, and non-printable characters
        $content = trim($content);
        $content = ltrim($content, "\xEF\xBB\xBF"); // Remove UTF-8 BOM if present

        // Remove any content after the closing </svg> tag (common cause of "extra content" error)
        if (preg_match('/^(.*<\/svg>)/s', $content, $matches)) {
            $content = $matches[1];
        }

        // Basic SVG validation - should start with XML declaration or <svg tag
        throw_if(
            ! str_starts_with($content, '<?xml') && ! str_starts_with($content, '<svg'),
            new Exception('Invalid SVG content: does not start with XML declaration or <svg> tag'),
        );

        // Try to load as XML to validate structure
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($content);

        if ($doc === false) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(fn ($error) => trim($error->message), $errors);
            libxml_clear_errors();

            throw new Exception('Invalid SVG XML structure: ' . implode(', ', $errorMessages));
        }

        libxml_clear_errors();

        return $content;
    }
}
