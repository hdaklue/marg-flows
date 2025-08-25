<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\ConversionContract;
use App\Services\Video\DTOs\ResolutionData;
use App\Services\Video\Enums\NamingPattern;
use App\Services\Video\Facades\Video;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ResolutionManager
{
    private string $sourcePath;
    private string $disk;
    private ?NamingPattern $namingStrategy;
    private array $conversions = [];
    private array $results = [];
    private ?\Closure $onStepSuccess = null;
    private ?\Closure $onStepFailure = null;

    public function __construct(string $sourcePath, string $disk = 'local', ?NamingPattern $namingStrategy = null)
    {
        $this->sourcePath = $sourcePath;
        $this->disk = $disk;
        $this->namingStrategy = $namingStrategy ?? NamingPattern::Conversion;
    }

    /**
     * Add 1080p conversion to the batch.
     */
    public function to1080p(): self
    {
        $this->conversions[] = app()->make(\App\Services\Video\Conversions\Conversion1080p::class);
        return $this;
    }

    /**
     * Add 720p conversion to the batch.
     */
    public function to720p(): self
    {
        $this->conversions[] = app()->make(\App\Services\Video\Conversions\Conversion720p::class);
        return $this;
    }

    /**
     * Add 480p conversion to the batch.
     */
    public function to480p(): self
    {
        $this->conversions[] = app()->make(\App\Services\Video\Conversions\Conversion480p::class);
        return $this;
    }

    /**
     * Add 360p conversion to the batch.
     */
    public function to360p(): self
    {
        $this->conversions[] = app()->make(\App\Services\Video\Conversions\Conversion360p::class);
        return $this;
    }

    /**
     * Add 240p conversion to the batch.
     */
    public function to240p(): self
    {
        $this->conversions[] = app()->make(\App\Services\Video\Conversions\Conversion240p::class);
        return $this;
    }

    /**
     * Add 144p conversion to the batch.
     */
    public function to144p(): self
    {
        $this->conversions[] = app()->make(\App\Services\Video\Conversions\Conversion144p::class);
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
            if (!$conversion instanceof ConversionContract) {
                throw new InvalidArgumentException('All conversions must implement ConversionContract');
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
     * @param \Closure(ResolutionData): void $closure
     */
    public function onStepSuccess(\Closure $closure): self
    {
        $this->onStepSuccess = $closure;
        return $this;
    }

    /**
     * Set closure to execute on each failed conversion step.
     * 
     * @param \Closure(ResolutionData): void $closure
     */
    public function onStepFailure(\Closure $closure): self
    {
        $this->onStepFailure = $closure;
        return $this;
    }

    /**
     * Execute all conversions and save to specified directory.
     * 
     * @return ResolutionData[]
     */
    public function saveTo(string $directory = ''): array
    {
        throw_if(empty($this->conversions), new InvalidArgumentException('No conversions specified'));

        $this->results = [];
        // If no directory specified, use same directory as source file
        // But avoid './' prefix for files in current directory
        $sourceDir = File::dirname($this->sourcePath);
        $outputDirectory = $directory ?: ($sourceDir === '.' ? '' : $sourceDir);

        foreach ($this->conversions as $conversion) {
            try {
                $outputPath = $this->generateOutputPath($outputDirectory, $conversion);
                
                Video::fromDisk($this->sourcePath, $this->disk)
                    ->convert($conversion)
                    ->save($outputPath);
                
                // Get file size using Storage disk path
                $fullStoragePath = Storage::disk($this->disk)->path($outputPath);
                $fileSize = File::exists($fullStoragePath) ? File::size($fullStoragePath) : 0;
                
                $step = ResolutionData::success(
                    get_class($conversion),
                    $outputPath,
                    $fileSize
                );
                
                $this->results[] = $step;
                
                // Execute success callback with current step DTO
                if ($this->onStepSuccess) {
                    ($this->onStepSuccess)($step);
                }
                
            } catch (\Exception $e) {
                $step = ResolutionData::failed(
                    get_class($conversion),
                    $e->getMessage()
                );
                
                $this->results[] = $step;
                
                // Execute failure callback with current step DTO
                if ($this->onStepFailure) {
                    ($this->onStepFailure)($step);
                }
            }
        }

        return $this->results;
    }

    /**
     * Execute all conversions and save to directory (alias for saveTo).
     */
    public function convert(string $directory = ''): array
    {
        return $this->saveTo($directory);
    }

    /**
     * Execute conversions and return only successful results.
     * 
     * @return ResolutionData[]
     */
    public function saveSuccessful(string $directory = ''): array
    {
        $results = $this->saveTo($directory);
        return array_filter($results, fn(ResolutionData $result) => $result->isSuccessful());
    }

    /**
     * Execute conversions and return only failed results.
     * 
     * @return ResolutionData[]
     */
    public function saveFailed(string $directory = ''): array
    {
        $results = $this->saveTo($directory);
        return array_filter($results, fn(ResolutionData $result) => $result->isFailed());
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
     * Get source path.
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * Generate output path using naming strategy.
     */
    private function generateOutputPath(string $directory, ConversionContract $conversion): string
    {
        $namingService = new VideoNamingService($this->namingStrategy);
        $filename = $namingService->generateName($this->sourcePath, $conversion);
        
        // If directory is empty (current directory), return just filename
        if (empty($directory)) {
            return $filename;
        }
        
        return rtrim($directory, '/') . '/' . $filename;
    }


    /**
     * Static factory method for fluent API.
     */
    public static function from(string $sourcePath, string $disk = 'local', ?NamingPattern $namingStrategy = null): self
    {
        return new self($sourcePath, $disk, $namingStrategy);
    }

    /**
     * Static factory method from disk.
     */
    public static function fromDisk(string $sourcePath, string $disk = 'local', ?NamingPattern $namingStrategy = null): self
    {
        return new self($sourcePath, $disk, $namingStrategy);
    }
}