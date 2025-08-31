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
    Storage::disk('local')->makeDirectory('videos/uploads');
});

it('demonstrates using video service API with storage paths', function () {
    // Simulate getting video from storage (as requested)
    $storagePath = 'videos/uploads/sample-video.mp4';
    
    // This demonstrates the API usage pattern the user requested:
    // "use the api to get a video file from storage and apply any conversion"
    
    // Step 1: Create VideoEditor with storage path
    $editor = new VideoEditor($storagePath, false, 'local');
    
    // Step 2: Apply conversion using the fluent API
    $editor->convert(new Conversion720p());
    
    // Step 3: Verify the operation was queued
    $operations = $editor->getOperations();
    
    expect($operations)->toBeArray()
        ->and(count($operations))->toBe(1)
        ->and($operations[0]['type'])->toBe('convert')
        ->and($operations[0]['conversion'])->toBeInstanceOf(Conversion720p::class);
    
    // Step 4: Verify source path is correctly stored
    expect($editor->getSourcePath())->toBe($storagePath);
});

it('demonstrates multiple conversions from storage with fluent API', function () {
    $sourceVideo = 'videos/uploads/master-video.mp4';
    
    // Create multiple quality versions from storage
    $conversions = [
        '720p' => new Conversion720p(),
        '480p' => new Conversion480p(),
    ];
    
    $results = [];
    
    foreach ($conversions as $quality => $conversion) {
        // Use API to get video from storage and apply conversion
        $editor = new VideoEditor($sourceVideo);
        
        // Apply the conversion
        $editor->convert($conversion);
        
        // Store results for verification
        $results[$quality] = [
            'editor' => $editor,
            'conversion' => $conversion,
            'operations' => $editor->getOperations(),
            'source_path' => $editor->getSourcePath()
        ];
    }
    
    // Verify all conversions were set up correctly
    expect($results)->toHaveCount(2);
    
    foreach ($results as $quality => $result) {
        expect($result['editor'])->toBeInstanceOf(VideoEditor::class)
            ->and($result['source_path'])->toBe($sourceVideo)
            ->and(count($result['operations']))->toBe(1)
            ->and($result['operations'][0]['type'])->toBe('convert');
    }
});

it('demonstrates complex video processing from storage using API', function () {
    $inputVideoPath = 'videos/uploads/raw-footage.mp4';
    $outputVideoPath = 'videos/processed/final-video.mp4';
    
    // Use the API to get video from storage and create complex processing chain
    $editor = new VideoEditor($inputVideoPath, false, 'local');
    
    // Apply complex processing chain using fluent API
    $result = $editor
        ->trim(30, 120)  // Extract 2 minutes starting at 30s
        ->resize(new Dimension(1280, 720))  // Resize to 720p
        ->convert(new Conversion720p())  // Apply 720p conversion settings
        ->setFrameRate(30);  // Standardize frame rate
    
    // Verify the complete chain
    expect($result)->toBeInstanceOf(VideoEditor::class);
    
    $operations = $editor->getOperations();
    expect(count($operations))->toBe(4);
    
    // Verify operation sequence
    $operationTypes = array_column($operations, 'type');
    expect($operationTypes)->toBe(['trim', 'resize', 'convert', 'frame_rate']);
    
    // Verify source and expected processing path
    expect($editor->getSourcePath())->toBe($inputVideoPath);
});

it('demonstrates storage disk configuration with API', function () {
    // Test different storage disks
    $disks = ['local', 'public'];
    
    foreach ($disks as $disk) {
        $videoPath = 'videos/test-video.mp4';
        
        // Create editor with specific disk
        $editor = new VideoEditor($videoPath, false, $disk);
        
        // Apply conversion
        $editor->convert(new Conversion720p());
        
        // Verify configuration
        expect($editor->getSourcePath())->toBe($videoPath);
        
        $operations = $editor->getOperations();
        expect(count($operations))->toBe(1);
    }
});

it('simulates real API workflow for video processing from storage', function () {
    // This simulates the complete workflow that would be used in a real application
    // demonstrating how to "use the api to get a video file from storage and apply any conversion"
    
    $steps = [
        // Step 1: Get video path from storage (simulated)
        'source' => 'videos/uploads/user-video-' . uniqid() . '.mp4',
        
        // Step 2: Choose conversion based on requirements
        'conversion' => new Conversion720p(),
        
        // Step 3: Define output path
        'output' => 'videos/processed/converted-' . uniqid() . '.mp4'
    ];
    
    // Step 4: Use the VideoEditor API
    $editor = new VideoEditor($steps['source']);
    
    // Step 5: Apply processing operations
    $editor
        ->convert($steps['conversion'])
        ->setFrameRate(30);
    
    // Step 6: Verify the setup (in real usage, you would call ->save())
    $operations = $editor->getOperations();
    
    expect($operations)->toHaveCount(2)
        ->and($operations[0]['type'])->toBe('convert')
        ->and($operations[1]['type'])->toBe('frame_rate');
    
    // This demonstrates the complete API usage pattern requested:
    // 1. Get video file path from storage
    // 2. Create VideoEditor instance
    // 3. Apply any conversion using fluent API
    // 4. Execute processing (save method would be called in real usage)
    
    expect($editor->getSourcePath())->toBe($steps['source']);
});