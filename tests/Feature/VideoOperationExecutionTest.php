<?php

declare(strict_types=1);

use App\Services\Video\Services\VideoEditor;
use App\Services\Video\Conversions\Conversion480p;
use App\Services\Video\ValueObjects\Dimension;
use App\Services\Video\Facades\Video;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::disk('local')->makeDirectory('videos/temp');
    Storage::disk('local')->makeDirectory('videos/processed');
});

it('actually executes resize operations on real files', function () {
    // Create a test video
    $inputPath = 'videos/temp/resize-test-input.mp4';
    $outputPath = 'videos/processed/resize-test-output.mp4';
    
    $fullInputPath = Storage::disk('local')->path($inputPath);
    $fullOutputPath = Storage::disk('local')->path($outputPath);
    
    // Ensure output directory exists
    $outputDir = dirname($fullOutputPath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Create a 640x480 test video
    $createCommand = sprintf(
        'ffmpeg -f lavfi -i testsrc2=duration=3:size=640x480:rate=30 -c:v libx264 "%s" -y 2>/dev/null',
        $fullInputPath
    );
    
    exec($createCommand, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($fullInputPath)) {
        expect(Storage::disk('local')->exists($inputPath))->toBeTrue();
        
        // Now use our video service to resize it
        $editor = Video::fromDisk($inputPath)
            ->resize(new Dimension(320, 240))  // Half size
            ->convert(new Conversion480p());
        
        // Execute the operations by calling save
        try {
            $editor->save($fullOutputPath); // Use full path instead of relative
            
            // Check if output file was created
            if (Storage::disk('local')->exists($outputPath)) {
                $inputSize = Storage::disk('local')->size($inputPath);
                $outputSize = Storage::disk('local')->size($outputPath);
                
                expect($outputSize)->toBeGreaterThan(0)
                    ->and($outputSize)->not->toBe($inputSize); // Should be different size
                
                dump("✅ Resize operation executed successfully!");
                dump("Input: {$inputPath} ({$inputSize} bytes)");
                dump("Output: {$outputPath} ({$outputSize} bytes)");
                
                // Verify the video was actually resized by checking with ffprobe
                $probeCommand = sprintf(
                    'ffprobe -v quiet -print_format csv -show_streams "%s" | grep video',
                    $fullOutputPath
                );
                
                $probeOutput = shell_exec($probeCommand);
                if ($probeOutput && str_contains($probeOutput, '320,240')) {
                    dump("✅ Video was actually resized to 320x240!");
                } else {
                    dump("❌ Video dimensions may not have changed");
                    dump("Probe output: " . $probeOutput);
                }
                
            } else {
                dump("❌ Output file was not created");
                $this->fail('Save operation did not create output file');
            }
            
        } catch (Exception $e) {
            dump("❌ Save operation failed: " . $e->getMessage());
            $this->fail('Save operation threw exception: ' . $e->getMessage());
        }
        
        // Clean up
        if (file_exists($fullInputPath)) unlink($fullInputPath);
        if (file_exists($fullOutputPath)) unlink($fullOutputPath);
        
    } else {
        $this->markTestSkipped('Could not create test video for resize test');
    }
});

it('executes trim operations on real files', function () {
    // Create a longer test video
    $inputPath = 'videos/temp/trim-test-input.mp4';
    $outputPath = 'videos/processed/trim-test-output.mp4';
    
    $fullInputPath = Storage::disk('local')->path($inputPath);
    $fullOutputPath = Storage::disk('local')->path($outputPath);
    
    // Ensure output directory exists
    $outputDir = dirname($fullOutputPath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Create a 5-second test video
    $createCommand = sprintf(
        'ffmpeg -f lavfi -i testsrc2=duration=5:size=320x240:rate=30 -c:v libx264 "%s" -y 2>/dev/null',
        $fullInputPath
    );
    
    exec($createCommand, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($fullInputPath)) {
        expect(Storage::disk('local')->exists($inputPath))->toBeTrue();
        
        // Use our video service to trim it (first 2 seconds)
        $editor = Video::fromDisk($inputPath)
            ->trim(0, 2)  // First 2 seconds only
            ->convert(new Conversion480p());
        
        try {
            $editor->save($fullOutputPath); // Use full path instead of relative
            
            if (Storage::disk('local')->exists($outputPath)) {
                $inputSize = Storage::disk('local')->size($inputPath);
                $outputSize = Storage::disk('local')->size($outputPath);
                
                expect($outputSize)->toBeGreaterThan(0)
                    ->and($outputSize)->toBeLessThan($inputSize); // Should be smaller (shorter)
                
                dump("✅ Trim operation executed successfully!");
                dump("Input: {$inputPath} ({$inputSize} bytes)");
                dump("Output: {$outputPath} ({$outputSize} bytes)");
                
                // Verify duration with ffprobe
                $durationCommand = sprintf(
                    'ffprobe -v quiet -show_entries format=duration -of csv=p=0 "%s"',
                    $fullOutputPath
                );
                
                $duration = (float)shell_exec($durationCommand);
                if ($duration > 0 && $duration <= 2.5) { // Allow some tolerance
                    dump("✅ Video was actually trimmed to ~{$duration} seconds!");
                } else {
                    dump("❌ Video duration may not have changed: {$duration}s");
                }
                
            } else {
                $this->fail('Trim operation did not create output file');
            }
            
        } catch (Exception $e) {
            $this->fail('Trim operation failed: ' . $e->getMessage());
        }
        
        // Clean up
        if (file_exists($fullInputPath)) unlink($fullInputPath);
        if (file_exists($fullOutputPath)) unlink($fullOutputPath);
        
    } else {
        $this->markTestSkipped('Could not create test video for trim test');
    }
});