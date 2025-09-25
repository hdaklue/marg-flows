<?php

declare(strict_types=1);

use FFMpeg\Coordinate\Dimension;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

it('can directly process video with Laravel FFmpeg', function () {
    // Use the existing test video
    $inputPath = 'videos/temp/test-video.mp4';
    $outputPath = 'videos/temp/direct-test-output.mp4';

    // Ensure the test video exists
    expect(Storage::disk('local')->exists($inputPath))->toBeTrue();

    $fullOutputPath = Storage::disk('local')->path($outputPath);
    $outputDir = dirname($fullOutputPath);
    if (! is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    try {
        // Direct Laravel FFmpeg processing
        FFMpeg::fromDisk('local')
            ->open($inputPath)
            ->export()
            ->addFilter(function ($filters) {
                $filters->resize(new Dimension(320, 240), 'fit');
            })
            ->save($fullOutputPath);

        // Verify output
        expect(file_exists($fullOutputPath))->toBeTrue();

        if (file_exists($fullOutputPath)) {
            $inputSize = Storage::disk('local')->size($inputPath);
            $outputSize = filesize($fullOutputPath);

            expect($outputSize)->toBeGreaterThan(0);

            dump('✅ Direct Laravel FFmpeg works!');
            dump("Input: {$inputSize} bytes");
            dump("Output: {$outputSize} bytes");

            // Clean up
            unlink($fullOutputPath);
        }
    } catch (\Exception $e) {
        $this->fail('Direct Laravel FFmpeg failed: ' . $e->getMessage());
    }
});
