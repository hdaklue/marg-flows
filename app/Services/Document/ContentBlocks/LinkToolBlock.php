<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

use BumpCore\EditorPhp\Block\Block;
use Exception;
use Faker\Generator;

final class LinkToolBlock extends Block
{
    /**
     * Get allowed URL schemes.
     */
    public static function getAllowedSchemes(): array
    {
        return ['http', 'https'];
    }

    /**
     * Get blocked file extensions that should not be processed as links.
     */
    public static function getBlockedExtensions(): array
    {
        return [
            'mp4',
            'webm',
            'ogg',
            'mov',
            'avi',
            'wmv',
            'flv',
            'mkv',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'svg',
            'webp',
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt',
            'zip',
            'rar',
            'tar',
            'gz',
            'mp3',
            'wav',
            'ogg',
            'flac',
        ];
    }

    /**
     * Get blocked domains that should not be processed (e.g., handled by other plugins).
     */
    public static function getBlockedDomains(): array
    {
        return [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'dailymotion.com',
        ];
    }

    /**
     * Generate fake data for testing.
     */
    public static function fake(Generator $faker): array
    {
        $enablePreview = $faker->boolean(80); // 80% chance to have preview enabled

        $data = [
            'link' => $faker->url(),
            'enablePreview' => $enablePreview,
        ];

        if ($enablePreview) {
            $data['meta'] = [
                'title' => $faker->sentence(),
                'description' => $faker->optional(0.8)->paragraph(),
                'image' => $faker->optional(0.6)->imageUrl(400, 300),
            ];
        } else {
            $data['meta'] = [];
        }

        return $data;
    }

    /**
     * Validation rules for the link tool block data.
     */
    public function rules(): array
    {
        return [
            'link' => ['required', 'string', 'url'],
            'enablePreview' => ['boolean'],
            'meta' => ['nullable', 'array'],
            'meta.title' => ['nullable', 'string', 'max:500'],
            'meta.description' => ['nullable', 'string', 'max:1000'],
            'meta.image' => ['nullable', 'string', 'url'],
        ];
    }

    /**
     * Allowed HTML tags for content purification.
     */
    public function allows(): array
    {
        return [
            'meta.title' => 'b,i,em,strong', // Allow basic formatting in titles
            'meta.description' => 'b,i,em,strong,br', // Allow basic formatting and breaks in descriptions
        ];
    }

    /**
     * Check if the block has a valid link.
     */
    public function hasLink(): bool
    {
        $link = $this->get('link');

        return !empty($link) && is_string($link) && trim($link) !== '' && $this->isValidUrl($link);
    }

    /**
     * Get the link URL.
     */
    public function getLink(): string
    {
        return (string) $this->get('link', '');
    }

    /**
     * Check if preview is enabled for this link.
     */
    public function isPreviewEnabled(): bool
    {
        return (bool) $this->get('enablePreview', true); // Default to true
    }

    /**
     * Get link metadata.
     */
    public function getMeta(): array
    {
        $meta = $this->get('meta', []);

        return is_array($meta) ? $meta : [];
    }

    /**
     * Get link title from metadata.
     */
    public function getTitle(): null|string
    {
        $meta = $this->getMeta();

        return !empty($meta['title']) ? (string) $meta['title'] : null;
    }

    /**
     * Get link description from metadata.
     */
    public function getDescription(): null|string
    {
        $meta = $this->getMeta();

        return !empty($meta['description']) ? (string) $meta['description'] : null;
    }

    /**
     * Get link image from metadata.
     */
    public function getImage(): null|string
    {
        $meta = $this->getMeta();

        return !empty($meta['image']) ? (string) $meta['image'] : null;
    }

    /**
     * Get hostname from the link URL.
     */
    public function getHostname(): string
    {
        $link = $this->getLink();

        if (empty($link)) {
            return '';
        }

        $parsed = parse_url($link);

        return $parsed['host'] ?? $link;
    }

    /**
     * Check if the link has metadata for preview.
     */
    public function hasMetadata(): bool
    {
        $meta = $this->getMeta();

        return (
            !empty($meta)
            && (!empty($meta['title']) || !empty($meta['description']) || !empty($meta['image']))
        );
    }

    /**
     * Check if the link should show as preview.
     */
    public function shouldShowPreview(): bool
    {
        return $this->isPreviewEnabled() && $this->hasMetadata();
    }

    /**
     * Validate URL format and constraints.
     */
    public function isValidUrl(string $url): bool
    {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);

        // Check scheme
        if (
            empty($parsed['scheme'])
            || !in_array(strtolower($parsed['scheme']), self::getAllowedSchemes())
        ) {
            return false;
        }

        // Check if domain is blocked
        $host = $parsed['host'] ?? '';
        foreach (self::getBlockedDomains() as $blockedDomain) {
            if (str_ends_with($host, $blockedDomain)) {
                return false;
            }
        }

        // Check if URL ends with blocked file extension
        $path = $parsed['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!empty($extension) && in_array($extension, self::getBlockedExtensions())) {
            return false;
        }

        return true;
    }

    /**
     * Validate link constraints.
     */
    public function validateLinkConstraints(array $constraints = []): array
    {
        $errors = [];

        if (!$this->hasLink()) {
            return $errors;
        }

        $link = $this->getLink();

        // Validate URL format
        if (!$this->isValidUrl($link)) {
            $errors[] = 'Invalid URL format or blocked domain/file type';
        }

        // Validate against custom domain whitelist
        if (!empty($constraints['allowed_domains'])) {
            $hostname = $this->getHostname();
            $isAllowed = false;

            foreach ($constraints['allowed_domains'] as $allowedDomain) {
                if (str_ends_with($hostname, $allowedDomain)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                $allowedList = implode(', ', $constraints['allowed_domains']);
                $errors[] = "Domain ({$hostname}) is not in the allowed domains list: {$allowedList}";
            }
        }

        // Validate against custom domain blacklist
        if (!empty($constraints['blocked_domains'])) {
            $hostname = $this->getHostname();

            foreach ($constraints['blocked_domains'] as $blockedDomain) {
                if (str_ends_with($hostname, $blockedDomain)) {
                    $errors[] = "Domain ({$hostname}) is blocked";
                    break;
                }
            }
        }

        // Validate metadata if preview is enabled
        if ($this->isPreviewEnabled() && !empty($constraints['require_metadata'])) {
            if (!$this->hasMetadata()) {
                $errors[] = 'Link preview is enabled but metadata is missing or invalid';
            }
        }

        return $errors;
    }

    /**
     * Check if the block is empty (no valid link).
     */
    public function isEmpty(): bool
    {
        return !$this->hasLink();
    }

    /**
     * Get display text for the link.
     */
    public function getDisplayText(): string
    {
        if (!$this->hasLink()) {
            return '';
        }

        // If preview is enabled and has title, use title
        if ($this->shouldShowPreview() && $this->getTitle()) {
            return $this->getTitle();
        }

        // Otherwise, use hostname
        return $this->getHostname();
    }

    /**
     * Render the link tool block to HTML.
     */
    public function render(): string
    {
        throw new Exception('Create a separate view for this block');
    }

    /**
     * Render the link tool block to HTML with RTL support.
     */
    public function renderRtl(): string
    {
        throw new Exception('Create a separate view for this block');
    }

    /**
     * Get summary data for analytics or reporting.
     */
    public function getSummary(): array
    {
        return [
            'type' => 'link',
            'link' => $this->getLink(),
            'hostname' => $this->getHostname(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'image' => $this->getImage(),
            'enable_preview' => $this->isPreviewEnabled(),
            'has_metadata' => $this->hasMetadata(),
            'should_show_preview' => $this->shouldShowPreview(),
            'display_text' => $this->getDisplayText(),
            'is_valid_url' => $this->isValidUrl($this->getLink()),
            'is_empty' => $this->isEmpty(),
        ];
    }
}
