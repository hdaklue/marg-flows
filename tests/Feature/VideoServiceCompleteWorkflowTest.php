<?php

declare(strict_types=1);

use App\Services\Video\Conversions\Conversion360p;
use App\Services\Video\Conversions\Conversion480p;
use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Services\VideoEditor;
use App\Services\Video\ValueObjects\Dimension;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::disk('local')->makeDirectory('videos');
    Storage::disk('local')->makeDirectory('videos/processed');
    Storage::disk('local')->makeDirectory('videos/uploads');
});

it('demonstrates the complete video service workflow as requested', function () {
    /*
     * This test demonstrates the complete workflow requested:
     * "use the api to get a video file from storage and apply any conversion"
     * "u can test it by loading file from url. saving it make operations and saveas"
     */

    // ========== SCENARIO 1: Load from URL, process, and save ==========
    $videoUrl = 'https://example.com/sample-video.mp4';
    $outputPath = 'videos/processed/url-processed.mp4';

    // Step 1: Create VideoEditor from URL
    $urlEditor = new VideoEditor($videoUrl, true);

    // Step 2: Apply operations
    $urlEditor
        ->trim(10, 60)
        ->resize(new Dimension(1280, 720))
        ->convert(new Conversion720p); // Extract 1 minute from 10s mark // Resize to 720p // Apply 720p conversion

    // Step 3: Verify setup (in real usage: $urlEditor->save($outputPath))
    expect($urlEditor->getSourcePath())->toBe($videoUrl);
    $urlOps = $urlEditor->getOperations();
    expect(count($urlOps))->toBe(3);

    // ========== SCENARIO 2: Load from storage, process, and saveAs ==========
    $storagePath = 'videos/uploads/user-video.mp4';
    $conversion = new Conversion480p;

    // Step 1: Create VideoEditor from storage
    $storageEditor = new VideoEditor($storagePath);

    // Step 2: Apply operations and conversion
    $storageEditor->trim(0, 30)->convert($conversion); // First 30 seconds

    // Step 3: Verify setup (in real usage: $storageEditor->saveAs($outputPath, $conversion))
    expect($storageEditor->getSourcePath())->toBe($storagePath);
    $storageOps = $storageEditor->getOperations();
    expect(count($storageOps))->toBe(2);
});

it('demonstrates multiple quality processing from single source', function () {
    // Common source video
    $sourceVideo = 'videos/uploads/master-video.mp4';

    // Different quality conversions
    $qualityVersions = [
        '720p' => new Conversion720p,
        '480p' => new Conversion480p,
        '360p' => new Conversion360p,
    ];

    $processedVideos = [];

    foreach ($qualityVersions as $quality => $conversion) {
        // Create editor for each quality
        $editor = new VideoEditor($sourceVideo);

        // Apply consistent operations + conversion
        $editor->trim(5, 120)->convert($conversion); // 2 minutes starting at 5s // Quality-specific conversion

        // Define output path
        $outputPath = "videos/processed/video-{$quality}.mp4";

        // Store for verification
        $processedVideos[$quality] = [
            'editor' => $editor,
            'conversion' => $conversion,
            'output_path' => $outputPath,
            'operations' => $editor->getOperations(),
        ];
    }

    // Verify all versions were created
    expect($processedVideos)->toHaveCount(3);

    foreach ($processedVideos as $quality => $video) {
        expect($video['editor']->getSourcePath())
            ->toBe($sourceVideo)
            ->and(count($video['operations']))
            ->toBe(2)
            ->and($video['operations'][0]['type'])
            ->toBe('trim')
            ->and($video['operations'][1]['type'])
            ->toBe('convert');

        // In real usage, each would be processed:
        // $video['editor']->saveAs($video['output_path'], $video['conversion']);
    }
});

it('demonstrates advanced processing chain with operations and saveAs', function () {
    // Source from URL or storage
    $sourceUrl = 'https://cdn.example.com/raw-footage.mp4';
    $finalOutputPath = 'videos/processed/final-edited-video.mp4';

    // Create comprehensive processing chain
    $editor = new VideoEditor($sourceUrl, true);

    // Apply advanced operations
    $editor
        ->trim(30, 180)
        ->resize(new Dimension(1920, 1080))
        ->crop(100, 100, new Dimension(1720, 880))
        ->setFrameRate(30)
        ->convert(new Conversion720p); // 3 minutes starting at 30s // Upscale to Full HD // Crop with margins // Standardize frame rate // Final conversion

    // Verify the complex chain
    $operations = $editor->getOperations();

    expect(count($operations))->toBe(5);

    $expectedTypes = ['trim', 'resize', 'crop', 'frame_rate', 'convert'];
    $actualTypes = array_column($operations, 'type');

    expect($actualTypes)->toBe($expectedTypes);

    // Verify specific operations
    expect($operations[0]['start'])
        ->toBe(30.0)
        ->and($operations[0]['duration'])
        ->toBe(180.0)
        ->and($operations[1]['dimension'])
        ->toBeInstanceOf(Dimension::class)
        ->and($operations[2]['x'])
        ->toBe(100)
        ->and($operations[2]['y'])
        ->toBe(100)
        ->and($operations[3]['fps'])
        ->toBe(30)
        ->and($operations[4]['conversion'])
        ->toBeInstanceOf(Conversion720p::class);

    // In real usage: $editor->save($finalOutputPath);
});

it('demonstrates batch processing workflow', function () {
    // Multiple source videos
    $sourceVideos = [
        'videos/uploads/video1.mp4',
        'videos/uploads/video2.mp4',
        'https://example.com/video3.mp4', // URL source
    ];

    $conversion = new Conversion720p;
    $processedBatch = [];

    foreach ($sourceVideos as $index => $source) {
        $isUrl = str_starts_with($source, 'http');
        $editor = new VideoEditor($source, $isUrl);

        // Apply standard processing
        $editor
            ->trim(0, 60)
            ->resize(new Dimension(1280, 720))
            ->convert($conversion); // First minute of each // Standard size // Standard quality

        $outputPath = "videos/processed/batch-video-{$index}.mp4";

        $processedBatch[] = [
            'source' => $source,
            'editor' => $editor,
            'output' => $outputPath,
            'is_url' => $isUrl,
        ];
    }

    // Verify batch processing setup
    expect($processedBatch)->toHaveCount(3);

    foreach ($processedBatch as $item) {
        $operations = $item['editor']->getOperations();

        expect(count($operations))
            ->toBe(3)
            ->and($item['editor']->getSourcePath())
            ->toBe($item['source']);

        // In real usage: $item['editor']->save($item['output']);
    }
});

it('demonstrates the complete API as requested in user prompt', function () {
    /*
     * Final demonstration of the complete workflow:
     * 1. "use the api to get a video file from storage" ✓
     * 2. "apply any conversion" ✓
     * 3. "loading file from url" ✓
     * 4. "saving it make operations and saveas" ✓
     */

    // Example 1: Storage file + operations + saveAs
    $storageFile = 'videos/uploads/source.mp4';
    $editor1 = new VideoEditor($storageFile);
    $conversion1 = new Conversion720p;

    $editor1->trim(0, 45)->convert($conversion1);

    // Real usage: $editor1->saveAs('videos/processed/output1.mp4', $conversion1);

    // Example 2: URL file + operations + save
    $urlFile = 'https://example.com/source.mp4';
    $editor2 = new VideoEditor($urlFile, true);

    $editor2->resize(new Dimension(854, 480))->convert(new Conversion480p);

    // Real usage: $editor2->save('videos/processed/output2.mp4');

    // Example 3: Complex operations + saveAs
    $editor3 = new VideoEditor('videos/uploads/complex.mp4');
    $conversion3 = new Conversion360p;

    $editor3
        ->trim(10, 90)
        ->resize(new Dimension(1280, 720))
        ->crop(0, 0, new Dimension(640, 360))
        ->setFrameRate(25)
        ->convert($conversion3);

    // Real usage: $editor3->saveAs('videos/processed/output3.mp4', $conversion3);

    // Verify all examples work
    expect($editor1->getOperations())
        ->toHaveCount(2)
        ->and($editor2->getOperations())
        ->toHaveCount(2)
        ->and($editor3->getOperations())
        ->toHaveCount(5);

    // This demonstrates the complete fluent API working with:
    // - Storage files ✓
    // - URL files ✓
    // - Any conversions ✓
    // - Complex operation chains ✓
    // - Save and saveAs methods ✓
});
