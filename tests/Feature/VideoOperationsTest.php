<?php

declare(strict_types=1);

use App\Services\Video\Operations\CropOperation;
use App\Services\Video\Operations\ResizeOperation;
use App\Services\Video\Operations\TrimOperation;
use App\Services\Video\Operations\WatermarkOperation;
use App\Services\Video\Pipeline\VideoOperationPipeline;
use App\Services\Video\ValueObjects\Dimension;

it('creates resize operation with correct metadata', function () {
    $dimension = new Dimension(1280, 720);
    $operation = new ResizeOperation($dimension);

    expect($operation->getName())
        ->toBe('resize')
        ->and($operation->canExecute())
        ->toBeTrue()
        ->and($operation->getMetadata())
        ->toBeArray()
        ->and($operation->getMetadata()['width'])
        ->toBe(1280)
        ->and($operation->getMetadata()['height'])
        ->toBe(720);
});

it('creates crop operation with correct metadata', function () {
    $dimension = new Dimension(640, 480);
    $operation = new CropOperation(100, 50, $dimension);

    expect($operation->getName())
        ->toBe('crop')
        ->and($operation->canExecute())
        ->toBeTrue()
        ->and($operation->getMetadata())
        ->toBeArray()
        ->and($operation->getMetadata()['x'])
        ->toBe(100)
        ->and($operation->getMetadata()['y'])
        ->toBe(50)
        ->and($operation->getMetadata()['width'])
        ->toBe(640)
        ->and($operation->getMetadata()['height'])
        ->toBe(480);
});

it('validates crop operation parameters', function () {
    $dimension = new Dimension(640, 480);

    // Valid crop operation
    $validOperation = new CropOperation(0, 0, $dimension);
    expect($validOperation->canExecute())->toBeTrue();

    // Invalid crop operation with negative coordinates
    $invalidOperation = new CropOperation(-10, -5, $dimension);
    expect($invalidOperation->canExecute())->toBeFalse();

    // Invalid crop operation with zero dimensions would throw exception
    // This is handled by Dimension constructor validation
});

it('creates trim operation with correct metadata', function () {
    $operation = new TrimOperation(10.5, 60.0);

    expect($operation->getName())
        ->toBe('trim')
        ->and($operation->canExecute())
        ->toBeTrue()
        ->and($operation->getMetadata())
        ->toBeArray()
        ->and($operation->getMetadata()['start'])
        ->toBe(10.5)
        ->and($operation->getMetadata()['duration'])
        ->toBe(60.0)
        ->and($operation->getMetadata()['end'])
        ->toBe(70.5);
});

it('validates trim operation parameters', function () {
    // Valid trim operation
    $validOperation = new TrimOperation(0, 30);
    expect($validOperation->canExecute())->toBeTrue();

    // Invalid trim operation (negative duration)
    $invalidOperation = new TrimOperation(30, -10);
    expect($invalidOperation->canExecute())->toBeFalse();

    // Invalid trim operation (negative start)
    $negativeOperation = new TrimOperation(-5, 30);
    expect($negativeOperation->canExecute())->toBeFalse();
});

it('creates watermark operation with default parameters', function () {
    $operation = new WatermarkOperation('watermarks/logo.png');

    expect($operation->getName())
        ->toBe('watermark')
        ->and($operation->getMetadata())
        ->toBeArray()
        ->and($operation->getMetadata()['watermark_path'])
        ->toBe('watermarks/logo.png')
        ->and($operation->getMetadata()['position'])
        ->toBe('bottom-right')
        ->and($operation->getMetadata()['opacity'])
        ->toBe(1.0);
});

it('validates watermark operation parameters', function () {
    // Create a temporary watermark file for testing
    $tempFile = tempnam(sys_get_temp_dir(), 'watermark');
    file_put_contents($tempFile, 'fake image content');

    // Valid watermark operation
    $validOperation = new WatermarkOperation($tempFile);
    expect($validOperation->canExecute())->toBeTrue();

    // Clean up
    unlink($tempFile);

    // Invalid watermark operation (file doesn't exist)
    $invalidOperation = new WatermarkOperation('/non/existent/file.png');
    expect($invalidOperation->canExecute())->toBeFalse();

    // Invalid opacity
    $invalidOpacityOperation = new WatermarkOperation(
        'some/file.png',
        'top-left',
        1.5,
    );
    expect($invalidOpacityOperation->canExecute())->toBeFalse();
});

it('operations maintain execution order through index', function () {
    $pipeline = new VideoOperationPipeline('test-video.mp4');

    $operations = [
        new TrimOperation(0, 30),
        new ResizeOperation(new Dimension(1280, 720)),
        new CropOperation(0, 0, new Dimension(640, 480)),
    ];

    foreach ($operations as $operation) {
        $pipeline->addOperation($operation);
    }

    $metadata = $pipeline->getOperationsMetadata();

    expect($metadata[0]['operation'])
        ->toBe('trim')
        ->and($metadata[0]['priority'])
        ->toBe(0)
        ->and($metadata[1]['operation'])
        ->toBe('resize')
        ->and($metadata[1]['priority'])
        ->toBe(1)
        ->and($metadata[2]['operation'])
        ->toBe('crop')
        ->and($metadata[2]['priority'])
        ->toBe(2);
});

it('pipeline can add multiple operations at once', function () {
    $pipeline = new VideoOperationPipeline('test-video.mp4');

    $operations = [
        new TrimOperation(5, 25),
        new ResizeOperation(new Dimension(854, 480)),
    ];

    $pipeline->addOperations($operations);

    expect($pipeline->getOperationsCount())->toBe(2);
});

it('pipeline validates operation contracts', function () {
    $pipeline = new VideoOperationPipeline('test-video.mp4');

    // This should throw an exception for invalid operations
    expect(function () use ($pipeline) {
        $pipeline->addOperations([
            new TrimOperation(0, 30),
            'not an operation', // This should cause an error
        ]);
    })->toThrow(InvalidArgumentException::class);
});

it('operations can be skipped based on canExecute', function () {
    // Create an operation that cannot execute
    $invalidCrop = new CropOperation(-10, -5, new Dimension(640, 480));

    expect($invalidCrop->canExecute())->toBeFalse();

    // The operation should be marked as non-executable in metadata
    $metadata = $invalidCrop->getMetadata();
    expect($metadata['can_execute'])->toBeFalse();
});

it('dimension operations work correctly', function () {
    $dimension = new Dimension(1920, 1080);

    // Test pixel count calculation
    expect($dimension->getPixelCount())->toBe(2073600);

    // Test aspect ratio calculation (16:9 = 1.777...)
    expect($dimension->getAspectRatio())
        ->toBeInstanceOf(\App\Services\Video\ValueObjects\AspectRatio::class);

    // Test scaling to smaller dimensions
    $smaller = new Dimension(960, 540);
    $scaled = $dimension->scaleTo($smaller->getWidth(), $smaller->getHeight());

    expect($scaled->getWidth())
        ->toBe(960)
        ->and($scaled->getHeight())
        ->toBe(540);
});
