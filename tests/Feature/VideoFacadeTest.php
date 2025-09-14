<?php

declare(strict_types=1);

use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Facades\Video;
use App\Services\Video\Services\VideoEditor;
use App\Services\Video\ValueObjects\Dimension;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Ensure storage directories exist
    Storage::disk('local')->makeDirectory('videos');
    Storage::disk('local')->makeDirectory('videos/processed');
});

it('can create video editor using facade', function () {
    $videoPath = 'videos/test-video.mp4';

    $editor = Video::make($videoPath);

    expect($editor)
        ->toBeInstanceOf(VideoEditor::class)
        ->and($editor->getSourcePath())
        ->toBe($videoPath);
});

it('can create video editor from disk using facade', function () {
    $videoPath = 'videos/local-video.mp4';

    $editor = Video::fromDisk($videoPath);

    expect($editor)
        ->toBeInstanceOf(VideoEditor::class)
        ->and($editor->getSourcePath())
        ->toBe($videoPath);
});

it('can create video editor from public disk using facade', function () {
    $videoPath = 'videos/public-video.mp4';

    $editor = Video::fromPublic($videoPath);

    expect($editor)
        ->toBeInstanceOf(VideoEditor::class)
        ->and($editor->getSourcePath())
        ->toBe($videoPath);
});

it('can create video editor from URL using facade', function () {
    $videoUrl = 'https://example.com/video.mp4';

    $editor = Video::fromUrl($videoUrl);

    expect($editor)
        ->toBeInstanceOf(VideoEditor::class)
        ->and($editor->getSourcePath())
        ->toBe($videoUrl);
});

it('can chain operations using facade', function () {
    $videoPath = 'videos/facade-test.mp4';

    $result = Video::fromDisk($videoPath)
        ->trim(10, 60)
        ->resize(new Dimension(1280, 720))
        ->convert(new Conversion720p);

    expect($result)->toBeInstanceOf(VideoEditor::class);

    $operations = $result->getOperations();
    expect(count($operations))
        ->toBe(3)
        ->and($operations[0]['type'])
        ->toBe('trim')
        ->and($operations[1]['type'])
        ->toBe('resize')
        ->and($operations[2]['type'])
        ->toBe('convert');
});

it('demonstrates facade usage patterns', function () {
    // Pattern 1: Local file processing
    $localEditor = Video::fromDisk('videos/uploads/local.mp4')
        ->trim(0, 30)
        ->convert(new Conversion720p);

    // Pattern 2: URL processing
    $urlEditor = Video::fromUrl('https://example.com/remote.mp4')
        ->resize(new Dimension(854, 480))
        ->convert(new Conversion720p);

    // Pattern 3: Public disk processing
    $publicEditor = Video::fromPublic('videos/public.mp4')
        ->crop(0, 0, new Dimension(640, 360))
        ->setFrameRate(30);

    // Verify all patterns work
    expect($localEditor)
        ->toBeInstanceOf(VideoEditor::class)
        ->and($urlEditor)
        ->toBeInstanceOf(VideoEditor::class)
        ->and($publicEditor)
        ->toBeInstanceOf(VideoEditor::class);

    expect(count($localEditor->getOperations()))
        ->toBe(2)
        ->and(count($urlEditor->getOperations()))
        ->toBe(2)
        ->and(count($publicEditor->getOperations()))
        ->toBe(2);
});

it('can use facade for batch processing', function () {
    $sources = [
        'videos/video1.mp4',
        'videos/video2.mp4',
        'videos/video3.mp4',
    ];

    $editors = [];

    foreach ($sources as $source) {
        $editors[] = Video::fromDisk($source)
            ->trim(0, 45)
            ->convert(new Conversion720p);
    }

    expect(count($editors))->toBe(3);

    foreach ($editors as $editor) {
        expect($editor)
            ->toBeInstanceOf(VideoEditor::class)
            ->and(count($editor->getOperations()))
            ->toBe(2);
    }
});

it('demonstrates advanced facade usage', function () {
    // Complex processing chain using facade
    $editor = Video::fromUrl('https://example.com/source.mp4')
        ->trim(15, 120) // 2 minutes starting at 15s
        ->resize(new Dimension(1920, 1080)) // Full HD
        ->crop(100, 50, new Dimension(1720, 980)) // Crop with margins
        ->setFrameRate(30) // 30 FPS
        ->convert(new Conversion720p); // Final conversion

    $operations = $editor->getOperations();

    expect(count($operations))
        ->toBe(5)
        ->and($operations[0]['type'])
        ->toBe('trim')
        ->and($operations[1]['type'])
        ->toBe('resize')
        ->and($operations[2]['type'])
        ->toBe('crop')
        ->and($operations[3]['type'])
        ->toBe('frame_rate')
        ->and($operations[4]['type'])
        ->toBe('convert');

    // This demonstrates the complete fluent API through the facade
    expect($editor->getSourcePath())->toBe('https://example.com/source.mp4');
});
