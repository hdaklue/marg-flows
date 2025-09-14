<?php

declare(strict_types=1);

use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Services\VideoEditor;
use App\Services\Video\ValueObjects\AspectRatio;
use App\Services\Video\ValueObjects\Dimension;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Ensure storage directories exist
    Storage::disk('local')->makeDirectory('videos');
    Storage::disk('local')->makeDirectory('videos/processed');
});

it('can create a video editor instance', function () {
    $videoPath = 'videos/test-video.mp4';

    $editor = new VideoEditor($videoPath);

    expect($editor)->toBeInstanceOf(VideoEditor::class);
});

it('can add operations to video editor', function () {
    $videoPath = 'videos/test-video.mp4';
    $editor = new VideoEditor($videoPath);

    $dimension = new Dimension(1280, 720);

    $result = $editor->resize($dimension);

    expect($result)->toBeInstanceOf(VideoEditor::class);
});

it('can apply conversion to video editor', function () {
    $videoPath = 'videos/test-video.mp4';
    $editor = new VideoEditor($videoPath);

    $conversion = new Conversion720p;
    $result = $editor->convert($conversion);

    expect($result)->toBeInstanceOf(VideoEditor::class);
});

it('can trim video with time range', function () {
    $videoPath = 'videos/test-video.mp4';
    $editor = new VideoEditor($videoPath);

    $result = $editor->trim(10, 30); // 10 seconds to 30 seconds

    expect($result)->toBeInstanceOf(VideoEditor::class);
});

it('can chain multiple operations', function () {
    $videoPath = 'videos/test-video.mp4';
    $editor = new VideoEditor($videoPath);

    $dimension = new Dimension(854, 480);

    $result = $editor
        ->trim(5, 25)
        ->resize($dimension)
        ->convert(new Conversion720p);

    expect($result)->toBeInstanceOf(VideoEditor::class);
});

it('can get operations metadata', function () {
    $videoPath = 'videos/test-video.mp4';
    $editor = new VideoEditor($videoPath);

    $dimension = new Dimension(1280, 720);

    $editor->resize($dimension)->trim(10, 30);

    $operations = $editor->getOperations();

    expect($operations)->toBeArray()->and(count($operations))->toBe(2);
});

it('can build pipeline and get operations count', function () {
    $videoPath = 'videos/test-video.mp4';
    $editor = new VideoEditor($videoPath);

    $editor
        ->resize(new Dimension(1920, 1080))
        ->trim(0, 60)
        ->convert(new Conversion720p);

    $operations = $editor->getOperations();

    expect(count($operations))->toBe(3);
});

// Test with mock video processing (since we don't have actual video files)
it('can process video operations in correct order', function () {
    // This test will verify the operation ordering without actually processing video
    $videoPath = 'videos/mock-video.mp4';
    $editor = new VideoEditor($videoPath);

    // Add operations in specific order
    $editor->trim(5, 30)->resize(new Dimension(1280, 720))->crop(
        0,
        0,
        new Dimension(640, 480),
    ); // Should be index 0 // Should be index 1 // Should be index 2

    $operations = $editor->getOperations();

    expect($operations[0]['type'])
        ->toBe('trim')
        ->and($operations[1]['type'])
        ->toBe('resize')
        ->and($operations[2]['type'])
        ->toBe('crop');
});

// Test dimension calculations
it('correctly calculates dimension properties', function () {
    $dimension = new Dimension(1920, 1080);

    expect($dimension->getWidth())
        ->toBe(1920)
        ->and($dimension->getHeight())
        ->toBe(1080)
        ->and($dimension->getPixelCount())
        ->toBe(2073600)
        ->and($dimension->getAspectRatio())
        ->toBeInstanceOf(AspectRatio::class);
});

it('can scale dimensions correctly', function () {
    $dimension = new Dimension(1920, 1080);
    $targetDimension = new Dimension(960, 540);

    $scaledDimension = $dimension->scaleTo(
        $targetDimension->getWidth(),
        $targetDimension->getHeight(),
    );

    expect($scaledDimension->getWidth())
        ->toBe(960)
        ->and($scaledDimension->getHeight())
        ->toBe(540);
});

it('correctly handles scaling scenarios', function () {
    $smallDimension = new Dimension(640, 360); // Small video
    $largeDimension = new Dimension(1920, 1080); // Target 1080p

    // Scale to larger dimensions (upscaling allowed by default in scaleTo)
    $result = $smallDimension->scaleTo(
        $largeDimension->getWidth(),
        $largeDimension->getHeight(),
    );

    // The result should maintain aspect ratio but fit within bounds
    expect($result->getWidth())
        ->toBeLessThanOrEqual(1920)
        ->and($result->getHeight())
        ->toBeLessThanOrEqual(1080)
        ->and($result->getPixelCount())
        ->toBeGreaterThan($smallDimension->getPixelCount());
});
