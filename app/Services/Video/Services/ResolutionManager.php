<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\ConversionContract;
use App\Services\Video\DTOs\ResolutionData;
use App\Services\Video\Enums\NamingPattern;
use App\Services\Video\Resolutions\Resolution1080p;
use App\Services\Video\Resolutions\Resolution1440p;
use App\Services\Video\Resolutions\Resolution144p;
use App\Services\Video\Resolutions\Resolution240p;
use App\Services\Video\Resolutions\Resolution2K;
use App\Services\Video\Resolutions\Resolution360p;
use App\Services\Video\Resolutions\Resolution480p;
use App\Services\Video\Resolutions\Resolution4K;
use App\Services\Video\Resolutions\Resolution720p;
use App\Services\Video\Video;
use Closure;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Throwable;

final class ResolutionManager
{
    private ?NamingPattern $namingStrategy;

    private array $conversions = [];

    private array $results = [];

    private ?Closure $onStepSuccess = null;

    private ?Closure $onStepFailure = null;

    public function __construct(
        private readonly Video $video,
        ?NamingPattern $namingStrategy = null,
    ) {
        $this->namingStrategy = $namingStrategy ?? NamingPattern::ResolutionLabel;
    }

    /**
     * Static factory method for fluent API.
     */
    public static function from(
        string $sourcePath,
        string $disk = 'local',
        ?NamingPattern $namingStrategy = null,
    ): self {
        $video = Video::fromPath($sourcePath, $disk);

        return new self($video, $namingStrategy);
    }

    /**
     * Static factory method from disk.
     */
    public static function fromDisk(
        string $sourcePath,
        string $disk = 'local',
        ?NamingPattern $namingStrategy = null,
    ): self {
        $video = Video::fromPath($sourcePath, $disk);

        return new self($video, $namingStrategy);
    }

    /**
     * Static factory method from Video object.
     */
    public static function fromVideo(Video $video, ?NamingPattern $namingStrategy = null): self
    {
        return new self($video, $namingStrategy);
    }

    /**
     * Add 1440p conversion to the batch.
     */
    public function to1440p(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution1440p($orientation);

        return $this;
    }

    /**
     * Add 2K conversion to the batch.
     */
    public function to2K(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution2K($orientation);

        return $this;
    }

    /**
     * Add 4K conversion to the batch.
     */
    public function to4K(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution4K($orientation);

        return $this;
    }

    /**
     * Add 1080p conversion to the batch.
     */
    public function to1080p(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution1080p($orientation);

        return $this;
    }

    /**
     * Add a 720p conversion to the batch.
     */
    public function to720p(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution720p($orientation);

        return $this;
    }

    /**
     * Add 480p conversion to the batch.
     */
    public function to480p(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution480p($orientation);

        return $this;
    }

    /**
     * Add 360p conversion to the batch.
     */
    public function to360p(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution360p($orientation);

        return $this;
    }

    /**
     * Add 240p conversion to the batch.
     */
    public function to240p(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution240p($orientation);

        return $this;
    }

    /**
     * Add 144p conversion to the batch.
     */
    public function to144p(): self
    {
        $orientation = $this->video->getOrientation();
        $this->conversions[] = new Resolution144p($orientation);

        return $this;
    }

    /**
     * Add custom conversion to the batch.
     */
    public function addConversion(ConversionContract $conversion): self
    {
        $this->conversions[] = $conversion;

        return $this;
    }

    /**
     * Add multiple conversions at once.
     */
    public function addConversions(array $conversions): self
    {
        foreach ($conversions as $conversion) {
            if (! $conversion instanceof ConversionContract) {
                throw new InvalidArgumentException(
                    'All conversions must implement ConversionContract',
                );
            }
            $this->addConversion($conversion);
        }

        return $this;
    }

    /**
     * Set custom naming strategy for this batch.
     */
    public function withNamingStrategy(NamingPattern $strategy): self
    {
        $this->namingStrategy = $strategy;

        return $this;
    }

    /**
     * Set closure to execute on each successful conversion step.
     *
     * @param  Closure(ResolutionData): void  $closure
     */
    public function onStepSuccess(Closure $closure): self
    {
        $this->onStepSuccess = $closure;

        return $this;
    }

    /**
     * Set closure to execute on each failed conversion step.
     *
     * @param  Closure(ResolutionData): void  $closure
     */
    public function onStepFailure(Closure $closure): self
    {
        $this->onStepFailure = $closure;

        return $this;
    }

    /**
     * Execute all conversions and save to a specified directory.
     *
     * @return ResolutionData[]
     *
     * @throws Throwable
     */
    public function saveTo(string $directory = ''): array
    {
        throw_if(
            empty($this->conversions),
            new InvalidArgumentException('No conversions specified'),
        );

        $this->results = [];
        // If no directory specified, use the same directory as a source file
        $outputDirectory = $directory ?: $this->video->getDirectory();

        $exporter = ResolutionExporter::start($this->video->getPath(), $this->video->getDisk());

        foreach ($this->conversions as $conversion) {
            $outputPath = $this->generateOutputPath($outputDirectory, $conversion);
            $result = $exporter->export($conversion, $outputPath);

            $this->results[] = $result;

            // Execute success/failure callbacks
            if ($result->isSuccessful() && $this->onStepSuccess) {
                ($this->onStepSuccess)($result);
            } elseif ($result->isFailed() && $this->onStepFailure) {
                ($this->onStepFailure)($result);
            }
        }

        return $this->results;
    }

    /**
     * Execute all conversions and save to directory (alias for saveTo).
     *
     * @throws Throwable
     */
    public function convert(string $directory = ''): array
    {
        return $this->saveTo($directory);
    }

    /**
     * Get all queued conversions.
     */
    public function getConversions(): array
    {
        return $this->conversions;
    }

    /**
     * Get conversion results (after convert() has been called).
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Clear all conversions.
     */
    public function clear(): self
    {
        $this->conversions = [];
        $this->results = [];

        return $this;
    }

    /**
     * Get video object.
     */
    public function getVideo(): Video
    {
        return $this->video;
    }

    /**
     * Get source path.
     */
    public function getSourcePath(): string
    {
        return $this->video->getPath();
    }

    /**
     * Generate an output path using naming strategy.
     */
    private function generateOutputPath(string $directory, ConversionContract $conversion): string
    {
        $namingService = new VideoNamingService($this->namingStrategy);
        $filename = $namingService->generateName($this->video, $conversion);

        // If the directory is empty (current directory), return just the filename
        if (empty($directory)) {
            return $filename;
        }

        return rtrim($directory, '/') . '/' . $filename;
    }
}
