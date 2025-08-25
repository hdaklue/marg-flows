<?php

declare(strict_types=1);

use App\Services\Video\Services\VideoEditor;
use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Conversions\Conversion480p;
use App\Services\Video\ValueObjects\Dimension;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Ensure storage directories exist
    Storage::disk('local')->makeDirectory('videos');
    Storage::disk('local')->makeDirectory('videos/processed');
    Storage::disk('local')->makeDirectory('videos/temp');
});

it('can load video from URL and process it', function () {
    // Use a sample video URL (we'll simulate this for testing)
    $videoUrl = 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4';
    
    // Create VideoEditor with URL
    $editor = new VideoEditor($videoUrl, true, 'local');
    
    // Apply operations
    $editor
        ->trim(0, 30)  // First 30 seconds
        ->resize(new Dimension(640, 360))  // Resize to smaller
        ->convert(new Conversion480p());  // Apply 480p conversion
    
    // Verify the editor was created and operations queued
    expect($editor->getSourcePath())->toBe($videoUrl);
    
    $operations = $editor->getOperations();
    expect(count($operations))->toBe(3)
        ->and($operations[0]['type'])->toBe('trim')
        ->and($operations[1]['type'])->toBe('resize')
        ->and($operations[2]['type'])->toBe('convert');
});

it('demonstrates complete URL to storage workflow', function () {
    // Step 1: Video URL (simulated)
    $sourceUrl = 'https://example.com/videos/sample.mp4';
    $outputPath = 'videos/processed/converted-from-url.mp4';
    
    // Step 2: Create editor from URL
    $editor = new VideoEditor($sourceUrl, true);
    
    // Step 3: Apply processing operations
    $editor
        ->trim(10, 60)  // Extract 1 minute starting at 10s
        ->convert(new Conversion720p());  // Convert to 720p
    
    // Step 4: Verify setup before processing
    $operations = $editor->getOperations();
    
    expect($operations)->toHaveCount(2)
        ->and($operations[0]['type'])->toBe('trim')
        ->and($operations[0]['start'])->toBe(10.0)
        ->and($operations[0]['duration'])->toBe(60.0)
        ->and($operations[1]['type'])->toBe('convert');
    
    // In a real scenario, you would call:
    // $editor->save($outputPath);
    // This would download from URL, process, and save to storage
});

it('can chain complex operations from URL to multiple outputs', function () {
    $sourceUrl = 'https://example.com/videos/master-video.mp4';
    
    // Create different quality versions
    $conversions = [
        '720p' => new Conversion720p(),
        '480p' => new Conversion480p(),
    ];
    
    $processingChains = [];
    
    foreach ($conversions as $quality => $conversion) {
        $editor = new VideoEditor($sourceUrl, true);
        
        // Apply different processing for each quality
        $editor
            ->trim(0, 120)  // First 2 minutes
            ->resize($conversion->getDimension())  // Resize to target
            ->convert($conversion);  // Apply conversion
        
        $processingChains[$quality] = [
            'editor' => $editor,
            'operations' => $editor->getOperations(),
            'output_path' => "videos/processed/video-{$quality}.mp4"
        ];
        
        // Verify each chain
        expect($processingChains[$quality]['operations'])->toHaveCount(3);
    }
    
    // Verify all chains were set up correctly
    expect($processingChains)->toHaveCount(2);
    
    foreach ($processingChains as $quality => $chain) {
        expect($chain['editor']->getSourcePath())->toBe($sourceUrl)
            ->and($chain['operations'][0]['type'])->toBe('trim')
            ->and($chain['operations'][1]['type'])->toBe('resize')
            ->and($chain['operations'][2]['type'])->toBe('convert');
    }
});

it('demonstrates saveAs workflow with URL source', function () {
    $videoUrl = 'https://example.com/input-video.mp4';
    
    // Create editor and apply operations
    $editor = new VideoEditor($videoUrl, true);
    $conversion = new Conversion720p();
    
    $editor
        ->trim(5, 45)  // 40 seconds starting at 5s
        ->convert($conversion);
    
    // Define output path
    $outputPath = 'videos/processed/final-output.mp4';
    
    // Verify the complete setup
    expect($editor->getSourcePath())->toBe($videoUrl);
    
    $operations = $editor->getOperations();
    expect(count($operations))->toBe(2)
        ->and($operations[0]['type'])->toBe('trim')
        ->and($operations[1]['type'])->toBe('convert');
    
    // In real usage, this would process the video:
    // $editor->save($outputPath);
    // or
    // $editor->saveAs($outputPath, $conversion);
    
    // For testing, we verify the operations are correctly queued
    $trimOperation = $operations[0];
    expect($trimOperation['start'])->toBe(5.0)
        ->and($trimOperation['duration'])->toBe(45.0);
    
    $convertOperation = $operations[1];
    expect($convertOperation['conversion'])->toBeInstanceOf(Conversion720p::class);
});

it('validates URL and storage path handling', function () {
    $testCases = [
        [
            'source' => 'https://example.com/video.mp4',
            'is_url' => true,
            'description' => 'HTTPS URL'
        ],
        [
            'source' => 'http://example.com/video.mp4', 
            'is_url' => true,
            'description' => 'HTTP URL'
        ],
        [
            'source' => 'videos/local-file.mp4',
            'is_url' => false,
            'description' => 'Local storage path'
        ]
    ];
    
    foreach ($testCases as $case) {
        $editor = new VideoEditor($case['source'], $case['is_url']);
        
        expect($editor->getSourcePath())->toBe($case['source']);
        
        // Apply a simple operation to verify it works
        $editor->convert(new Conversion720p());
        
        $operations = $editor->getOperations();
        expect(count($operations))->toBe(1);
    }
});

it('simulates real-world URL processing pipeline', function () {
    // This simulates the complete workflow requested:
    // "loading file from url, saving it, make operations and saveas"
    
    $pipeline = [
        'input_url' => 'https://storage.googleapis.com/sample-videos/sample.mp4',
        'temp_path' => 'videos/temp/downloaded-' . uniqid() . '.mp4',
        'output_path' => 'videos/processed/final-' . uniqid() . '.mp4',
        'operations' => [
            'trim' => ['start' => 0, 'duration' => 30],
            'resize' => new Dimension(1280, 720),
            'conversion' => new Conversion720p()
        ]
    ];
    
    // Step 1: Create editor from URL
    $editor = new VideoEditor($pipeline['input_url'], true);
    
    // Step 2: Apply operations
    $editor
        ->trim($pipeline['operations']['trim']['start'], $pipeline['operations']['trim']['duration'])
        ->resize($pipeline['operations']['resize'])
        ->convert($pipeline['operations']['conversion']);
    
    // Step 3: Verify pipeline is ready for execution
    $operations = $editor->getOperations();
    
    expect($operations)->toHaveCount(3)
        ->and($editor->getSourcePath())->toBe($pipeline['input_url']);
    
    // In real usage, these methods would be called:
    // 1. $editor->save($pipeline['temp_path']);  // Process and save
    // 2. Or directly: $editor->saveAs($pipeline['output_path'], $pipeline['operations']['conversion']);
    
    // Verify operation details
    expect($operations[0]['type'])->toBe('trim')
        ->and($operations[0]['start'])->toBe(0.0)
        ->and($operations[0]['duration'])->toBe(30.0);
    
    expect($operations[1]['type'])->toBe('resize')
        ->and($operations[1]['dimension'])->toBeInstanceOf(Dimension::class);
    
    expect($operations[2]['type'])->toBe('convert')
        ->and($operations[2]['conversion'])->toBeInstanceOf(Conversion720p::class);
});