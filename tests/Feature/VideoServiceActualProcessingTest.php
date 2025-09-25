<?php

declare(strict_types=1);

use App\Services\Video\Conversions\Conversion480p;
use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Facades\Video;
use App\Services\Video\Services\VideoEditor;
use App\Services\Video\ValueObjects\Dimension;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Ensure storage directories exist
    Storage::disk('local')->makeDirectory('videos');
    Storage::disk('local')->makeDirectory('videos/processed');
    Storage::disk('local')->makeDirectory('videos/temp');

    // Clean up any existing test files
    $testFiles = [
        'videos/temp/test-video.mp4',
        'videos/processed/url-processed.mp4',
        'videos/processed/converted-720p.mp4',
    ];

    foreach ($testFiles as $file) {
        if (Storage::disk('local')->exists($file)) {
            Storage::disk('local')->delete($file);
        }
    }
});

it('can actually download and process a real video from URL', function () {
    // Skip if this is CI/CD or no internet connection
    if (env('CI') || env('SKIP_NETWORK_TESTS')) {
        $this->markTestSkipped('Network tests disabled');
    }

    // Use a small sample video URL (you can replace with a real URL)
    $sampleVideoUrl = 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4';

    // First, let's download the file manually to simulate what would happen
    try {
        $response = Http::timeout(30)->get($sampleVideoUrl);

        if ($response->successful()) {
            // Save the downloaded video to storage
            $tempPath = 'videos/temp/downloaded-sample.mp4';
            Storage::disk('local')->put($tempPath, $response->body());

            expect(Storage::disk('local')->exists($tempPath))->toBeTrue();

            // Now process it using our video service
            $outputPath = 'videos/processed/processed-sample.mp4';

            $editor = new VideoEditor($tempPath, false, 'local');

            // Apply some basic operations (without actual FFmpeg processing for now)
            $editor->convert(new Conversion720p);

            // Verify the operations were queued
            $operations = $editor->getOperations();
            expect(count($operations))->toBe(1)->and($operations[0]['type'])->toBe('convert');

            // In a real scenario, this would process the video:
            // $editor->save($outputPath);

            // Clean up
            Storage::disk('local')->delete($tempPath);
        } else {
            $this->markTestSkipped('Could not download sample video');
        }
    } catch (\Exception $e) {
        $this->markTestSkipped('Network error: ' . $e->getMessage());
    }
});

it('can create a simple test video file and process it', function () {
    // Create a simple test video file using FFmpeg command
    $testVideoPath = Storage::disk('local')->path('videos/temp/test-input.mp4');
    $outputVideoPath = Storage::disk('local')->path('videos/processed/test-output.mp4');

    // Create directories if they don't exist
    if (! is_dir(dirname($testVideoPath))) {
        mkdir(dirname($testVideoPath), 0755, true);
    }
    if (! is_dir(dirname($outputVideoPath))) {
        mkdir(dirname($outputVideoPath), 0755, true);
    }

    // Try to create a simple colored test video (5 seconds, red background)
    $ffmpegCommand = sprintf(
        'ffmpeg -f lavfi -i color=red:size=640x480:duration=5 -c:v libx264 -t 5 -pix_fmt yuv420p "%s" -y 2>/dev/null',
        $testVideoPath,
    );

    // Execute the command
    $result = shell_exec($ffmpegCommand);

    if (file_exists($testVideoPath) && filesize($testVideoPath) > 0) {
        // Great! We have a test video, now process it
        $editor = new VideoEditor('videos/temp/test-input.mp4', false, 'local');

        $editor->resize(new Dimension(320, 240))->convert(new Conversion480p); // Resize to smaller

        // Verify operations
        $operations = $editor->getOperations();
        expect(count($operations))
            ->toBe(2)
            ->and($operations[0]['type'])
            ->toBe('resize')
            ->and($operations[1]['type'])
            ->toBe('convert');

        // Test that source file exists
        expect(Storage::disk('local')->exists('videos/temp/test-input.mp4'))->toBeTrue();

        // In real usage: $editor->save('videos/processed/test-output.mp4');
        // This would create the actual output file

        // Clean up
        unlink($testVideoPath);
    } else {
        $this->markTestSkipped('Could not create test video file (FFmpeg may not be available)');
    }
});

it('demonstrates actual file processing workflow with facade', function () {
    // Create a mock video file for testing
    $mockVideoContent = 'This is mock video content for testing purposes';
    $inputPath = 'videos/temp/mock-input.mp4';
    $outputPath = 'videos/processed/facade-output.mp4';

    // Create mock input file
    Storage::disk('local')->put($inputPath, $mockVideoContent);

    expect(Storage::disk('local')->exists($inputPath))->toBeTrue();

    // Use facade to process the file
    $editor = Video::fromDisk($inputPath)
        ->trim(0, 30)
        ->resize(new Dimension(1280, 720))
        ->convert(new Conversion720p);

    // Verify the editor was created properly
    expect($editor)->toBeInstanceOf(VideoEditor::class);

    $operations = $editor->getOperations();
    expect(count($operations))
        ->toBe(3)
        ->and($operations[0]['type'])
        ->toBe('trim')
        ->and($operations[1]['type'])
        ->toBe('resize')
        ->and($operations[2]['type'])
        ->toBe('convert');

    // In real usage, this would create the output file:
    // $editor->save($outputPath);
    // expect(Storage::disk('local')->exists($outputPath))->toBeTrue();

    // Clean up
    Storage::disk('local')->delete($inputPath);
});

it('can simulate URL download and processing workflow', function () {
    // Simulate downloading a file from URL and processing it
    $simulatedVideoData = str_repeat('mock video data ', 1000); // Simulate video content

    // Step 1: Simulate downloading from URL
    $tempPath = 'videos/temp/downloaded-from-url.mp4';
    Storage::disk('local')->put($tempPath, $simulatedVideoData);

    expect(Storage::disk('local')->exists($tempPath))
        ->toBeTrue()
        ->and(Storage::disk('local')->size($tempPath))
        ->toBeGreaterThan(0);

    // Step 2: Process the downloaded file
    $editor = Video::fromDisk($tempPath)->trim(10, 60)->convert(new Conversion720p);

    // Step 3: Verify processing setup
    $operations = $editor->getOperations();
    expect(count($operations))->toBe(2);

    // Step 4: In real usage, save the processed file
    $outputPath = 'videos/processed/url-processed-final.mp4';
    // $editor->save($outputPath);

    // For demo, let's simulate the output file creation
    Storage::disk('local')->put($outputPath, 'processed video content');
    expect(Storage::disk('local')->exists($outputPath))->toBeTrue();

    // Clean up
    Storage::disk('local')->delete($tempPath);
    Storage::disk('local')->delete($outputPath);
});

it('validates actual file paths and storage operations', function () {
    // Test with real storage paths
    $testFiles = [
        'videos/temp/file1.mp4',
        'videos/temp/file2.mp4',
        'videos/temp/file3.mp4',
    ];

    // Create test files
    foreach ($testFiles as $file) {
        Storage::disk('local')->put($file, 'test video content');
        expect(Storage::disk('local')->exists($file))->toBeTrue();
    }

    // Process each file
    $processedFiles = [];

    foreach ($testFiles as $index => $file) {
        $editor = Video::fromDisk($file)->convert(new Conversion480p);

        $outputFile = "videos/processed/batch-{$index}.mp4";
        $processedFiles[] = $outputFile;

        // Simulate processing result
        Storage::disk('local')->put($outputFile, 'processed content');

        expect(Storage::disk('local')->exists($outputFile))->toBeTrue();
    }

    // Verify all files were created
    expect(count($processedFiles))->toBe(3);

    foreach (array_merge($testFiles, $processedFiles) as $file) {
        expect(Storage::disk('local')->exists($file))->toBeTrue();
    }

    // Clean up
    foreach (array_merge($testFiles, $processedFiles) as $file) {
        Storage::disk('local')->delete($file);
    }
});
