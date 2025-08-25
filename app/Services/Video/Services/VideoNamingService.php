<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\ConversionContract;
use App\Services\Video\Enums\NamingPattern;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VideoNamingService
{
    private NamingPattern $pattern;
    private array $variables = [];

    public function __construct(?NamingPattern $pattern = null)
    {
        $this->pattern = $pattern ?? NamingPattern::default();
    }

    public function setPattern(NamingPattern $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function addVariable(string $key, mixed $value): self
    {
        $this->variables[$key] = $value;
        return $this;
    }

    public function generateName(
        string $originalPath, 
        ConversionContract $conversion,
        ?NamingPattern $customPattern = null
    ): string {
        $pattern = $customPattern ?? $this->pattern;
        
        $variables = array_merge($this->getSystemVariables($originalPath, $conversion), $this->variables);
        
        return $this->processPattern($pattern->getPattern(), $variables);
    }

    public function generateMultipleNames(
        string $originalPath,
        array $conversions
    ): array {
        $names = [];
        
        foreach ($conversions as $key => $conversion) {
            $names[$key] = $this->generateName($originalPath, $conversion);
        }
        
        return $names;
    }

    private function getSystemVariables(string $originalPath, ConversionContract $conversion): array
    {
        $pathInfo = pathinfo($originalPath);
        $dimension = $conversion->getDimension();
        
        return [
            'basename' => $pathInfo['filename'] ?? 'video',
            'original_ext' => $pathInfo['extension'] ?? 'mp4',
            'ext' => $conversion->getFormat(),
            'format' => $conversion->getFormat(),
            'quality' => $conversion->getQuality(),
            'width' => $dimension?->getWidth() ?? 'auto',
            'height' => $dimension?->getHeight() ?? 'auto',
            'aspect_ratio' => str_replace(':', '-', $dimension?->getAspectRatio()->getAspectRatio() ?? 'auto'),
            'bitrate' => $conversion->getTargetBitrate() ?? 'auto',
            'conversion_name' => $conversion->getName(),
            'conversion_type' => $conversion->getType(),
            'timestamp' => Carbon::now()->format('YmdHis'),
            'date' => Carbon::now()->format('Ymd'),
            'time' => Carbon::now()->format('His'),
            'uuid' => Str::uuid()->toString(),
            'resolution_name' => str_replace(' ', '_', $dimension?->getAspectRatio()->getResolutionName() ?? 'custom'),
        ];
    }

    private function processPattern(string $pattern, array $variables): string
    {
        $result = $pattern;
        
        foreach ($variables as $key => $value) {
            $result = str_replace("{{$key}}", (string) $value, $result);
        }
        
        // Clean up any remaining placeholders
        $result = preg_replace('/\{[^}]*\}/', '', $result);
        
        // Clean up multiple underscores/dots
        $result = preg_replace('/[_]{2,}/', '_', $result);
        $result = preg_replace('/[.]{2,}/', '.', $result);
        
        return $result;
    }

    public static function make(): self
    {
        return new self();
    }

    // Predefined naming strategies
    public static function quality(): self
    {
        return new self(NamingPattern::Quality);
    }

    public static function dimension(): self
    {
        return new self(NamingPattern::Dimension);
    }

    public static function timestamped(): self
    {
        return new self(NamingPattern::Timestamped);
    }

    public static function detailed(): self
    {
        return new self(NamingPattern::Detailed);
    }

    public static function withPattern(NamingPattern $pattern): self
    {
        return new self($pattern);
    }
}