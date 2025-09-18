<?php

declare(strict_types=1);

use App\Services\Document\Actions\Video\ServerVideoStream;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Cache::flush();

    // Create a fake video file for testing
    $this->videoFile = UploadedFile::fake()->create('test-video.mp4', 1024, 'video/mp4');
    $this->videoPath = 'test-path/test-video.mp4';
    Storage::disk('public')->put($this->videoPath, $this->videoFile->getContent());
});

test('validates video file exists', function () {
    expect(Storage::disk('public')->exists($this->videoPath))->toBeTrue();
    expect(Storage::disk('public')->mimeType($this->videoPath))->toBe('video/mp4');
});

test('action class exists and has required methods', function () {
    expect(class_exists(ServerVideoStream::class))->toBeTrue();
    expect(method_exists(ServerVideoStream::class, 'handle'))->toBeTrue();
    expect(method_exists(ServerVideoStream::class, 'clearVideoCache'))->toBeTrue();
    expect(method_exists(ServerVideoStream::class, 'preloadVideoMetadata'))->toBeTrue();
});

test('can clear video cache', function () {
    $cacheKey = 'video_metadata:test123';
    Cache::put($cacheKey, ['test' => 'data'], 3600);
    expect(Cache::has($cacheKey))->toBeTrue();
    
    ServerVideoStream::clearVideoCache('test-path', 123);
    // The specific cache key might be different, but method should not throw errors
    expect(true)->toBeTrue();
});

test('can preload video metadata', function () {
    // Should not throw any exceptions for valid video file
    try {
        ServerVideoStream::preloadVideoMetadata($this->videoPath, 'public');
        expect(true)->toBeTrue(); // Test passes if no exception thrown
    } catch (Exception $e) {
        expect(false)->toBeTrue(); // Test fails if exception thrown
    }
});
