<?php

declare(strict_types=1);

use App\Services\Video\Conversions\Conversion480p;
use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Services\VideoEditor;
use App\Services\Video\ValueObjects\Dimension;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Ensure storage directories exist
    Storage::disk('local')->makeDirectory('videos');
    Storage::disk('local')->makeDirectory('videos/processed');
});

it('demonstrates complete video service API workflow', function () {
    // This test demonstrates the complete fluent API without requiring actual video files

    $sourcePath = 'videos/sample-video.mp4';

    // Create video editor instance
    $editor = new VideoEditor($sourcePath);

    // Chain multiple operations using fluent API
    $editor
        ->trim(10, 30)
        ->resize(new Dimension(1280, 720))
        ->convert(new Conversion720p); // Trim from 10s to 30s duration // Resize to 720p dimensions // Apply 720p conversion

    // Verify all operations were queued
    $operations = $editor->getOperations();

    expect(count($operations))
        ->toBe(3)
        ->and($operations[0]['type'])
        ->toBe('trim')
        ->and($operations[1]['type'])
        ->toBe('resize')
        ->and($operations[2]['type'])
        ->toBe('convert');
});

it('demonstrates multiple conversion workflow', function () {
    $sourcePath = 'videos/sample-video.mp4';

    // Create multiple conversions from the same source
    $conversions = [
        new Conversion720p,
        new Conversion480p,
    ];

    $results = [];

    foreach ($conversions as $conversion) {
        $editor = new VideoEditor($sourcePath);

        // Apply conversion and get operations
        $editor->convert($conversion);
        $operations = $editor->getOperations();

        $results[] = [
            'conversion' => $conversion->getName(),
            'bitrate' => $conversion->getTargetBitrate(),
            'dimensions' => $conversion->getDimension(),
            'operations_count' => count($operations),
        ];
    }

    expect(count($results))
        ->toBe(2)
        ->and($results[0]['conversion'])
        ->toBe('720p')
        ->and($results[0]['bitrate'])
        ->toBe(2500)
        ->and($results[1]['conversion'])
        ->toBe('480p')
        ->and($results[1]['bitrate'])
        ->toBe(1200);
});

it('demonstrates complex video manipulation chain', function () {
    $sourcePath = 'videos/complex-sample.mp4';

    $editor = new VideoEditor($sourcePath);

    // Create a temporary watermark file for testing
    $watermarkPath = tempnam(sys_get_temp_dir(), 'watermark');
    file_put_contents($watermarkPath, 'fake watermark content');

    // Complex manipulation chain
    $result = $editor
        ->trim(5, 45)
        ->resize(new Dimension(1920, 1080))
        ->crop(100, 100, new Dimension(1720, 880))
        ->watermark($watermarkPath, 'bottom-right', 0.8)
        ->convert(new Conversion720p)
        ->setFrameRate(30); // Extract 40 seconds starting at 5s // Resize to Full HD // Crop with 100px margin // Add watermark // Convert to 720p // Set frame rate

    $operations = $editor->getOperations();

    expect($result)
        ->toBeInstanceOf(VideoEditor::class)
        ->and(count($operations))
        ->toBe(6);

    // Verify operation types and order
    $operationTypes = array_column($operations, 'type');

    expect($operationTypes)->toBe([
        'trim',
        'resize',
        'crop',
        'watermark',
        'convert',
        'frame_rate',
    ]);

    // Clean up
    unlink($watermarkPath);
});

it('demonstrates dimension calculations and constraints', function () {
    // Test various dimension scenarios

    $scenarios = [
        [
            'source' => [640, 360],
            'target' => [1280, 720],
            'name' => '360p to 720p',
        ],
        [
            'source' => [1920, 1080],
            'target' => [854, 480],
            'name' => '1080p to 480p',
        ],
        [
            'source' => [1280, 720],
            'target' => [1280, 720],
            'name' => '720p to 720p (same)',
        ],
    ];

    foreach ($scenarios as $scenario) {
        $sourceDim = new Dimension(
            $scenario['source'][0],
            $scenario['source'][1],
        );
        $targetDim = new Dimension(
            $scenario['target'][0],
            $scenario['target'][1],
        );

        $scaledDim = $sourceDim->scaleTo(
            $targetDim->getWidth(),
            $targetDim->getHeight(),
        );

        // Verify scaled dimensions are valid
        expect($scaledDim)
            ->toBeInstanceOf(Dimension::class)
            ->and($scaledDim->getWidth())
            ->toBeGreaterThan(0)
            ->and($scaledDim->getHeight())
            ->toBeGreaterThan(0);
    }
});

it('demonstrates conversion constraints and quality settings', function () {
    $conversions = [
        new Conversion720p,
        new Conversion480p,
    ];

    // Test scale-up prevention
    $smallDimension = new Dimension(480, 270); // 270p source

    foreach ($conversions as $conversion) {
        $wouldScaleUp = $conversion->wouldScaleUp($smallDimension);
        $finalDimension = $conversion->calculateFinalDimension($smallDimension);

        expect($wouldScaleUp)
            ->toBeBool()
            ->and($finalDimension)
            ->toBeInstanceOf(Dimension::class);

        // Test conversion properties
        expect($conversion->getFormat())
            ->toBe('mp4')
            ->and($conversion->getTargetBitrate())
            ->toBeInt()
            ->and($conversion->getDimension())
            ->toBeInstanceOf(Dimension::class);
    }
});

it('can simulate video processing with operation pipeline', function () {
    // This test simulates the complete processing pipeline without actual video files

    $sourcePath = 'videos/test.mp4';
    $editor = new VideoEditor($sourcePath);

    // Build a complete processing pipeline
    $editor
        ->trim(0, 60)
        ->resize(new Dimension(1280, 720))
        ->convert(new Conversion720p); // First minute // Standardize to 720p // Apply quality conversion

    // Get the operations that would be executed
    $operations = $editor->getOperations();

    expect(count($operations))->toBe(3);

    // Verify each operation has the expected structure
    foreach ($operations as $operation) {
        expect($operation)
            ->toBeArray()
            ->and(array_key_exists('type', $operation))
            ->toBeTrue();
    }

    // Simulate getting source path (as would be used in actual processing)
    expect($editor->getSourcePath())->toBe($sourcePath);
});
