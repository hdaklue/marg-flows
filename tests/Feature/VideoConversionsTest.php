<?php

declare(strict_types=1);

use App\Services\Video\Resolutions\Resolution144p;
use App\Services\Video\Resolutions\Resolution240p;
use App\Services\Video\Resolutions\Resolution360p;
use App\Services\Video\Resolutions\Resolution480p;
use App\Services\Video\Resolutions\Resolution720p;
use App\Services\Video\Resolutions\Resolution1080p;
use App\Services\Video\Resolutions\Resolution1440p;
use App\Services\Video\Resolutions\Resolution4K;
use App\Services\Video\ValueObjects\Dimension;

it('creates 144p conversion with correct dimensions', function () {
    $resolution = new Resolution144p();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(256)
        ->and($resolution->getDimension()->getHeight())->toBe(144)
        ->and($resolution->getName())->toBe('144p')
        ->and($resolution->getTargetBitrate())->toBe(150);
});

it('creates 240p conversion with correct dimensions', function () {
    $resolution = new Resolution240p();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(426)
        ->and($resolution->getDimension()->getHeight())->toBe(240)
        ->and($resolution->getName())->toBe('240p')
        ->and($resolution->getTargetBitrate())->toBe(300);
});

it('creates 360p conversion with correct dimensions', function () {
    $resolution = new Resolution360p();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(640)
        ->and($resolution->getDimension()->getHeight())->toBe(360)
        ->and($resolution->getName())->toBe('360p')
        ->and($resolution->getTargetBitrate())->toBe(600);
});

it('creates 480p conversion with correct dimensions', function () {
    $resolution = new Resolution480p();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(854)
        ->and($resolution->getDimension()->getHeight())->toBe(480)
        ->and($resolution->getName())->toBe('480p')
        ->and($resolution->getTargetBitrate())->toBe(1000);
});

it('creates 720p conversion with correct dimensions', function () {
    $resolution = new Resolution720p();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(1280)
        ->and($resolution->getDimension()->getHeight())->toBe(720)
        ->and($resolution->getName())->toBe('720p')
        ->and($resolution->getTargetBitrate())->toBe(2500);
});

it('creates 1080p conversion with correct dimensions', function () {
    $resolution = new Resolution1080p();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(1920)
        ->and($resolution->getDimension()->getHeight())->toBe(1080)
        ->and($resolution->getName())->toBe('1080p')
        ->and($resolution->getTargetBitrate())->toBe(4500);
});

it('creates 1440p conversion with correct dimensions', function () {
    $resolution = new Resolution1440p();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(2560)
        ->and($resolution->getDimension()->getHeight())->toBe(1440)
        ->and($resolution->getName())->toBe('1440p')
        ->and($resolution->getTargetBitrate())->toBe(8000);
});

it('creates 4K conversion with correct dimensions', function () {
    $resolution = new Resolution4K();
    
    expect($resolution->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($resolution->getDimension()->getWidth())->toBe(3840)
        ->and($resolution->getDimension()->getHeight())->toBe(2160)
        ->and($resolution->getName())->toBe('4K')
        ->and($resolution->getTargetBitrate())->toBe(15000);
});

it('conversion respects scale-up constraints', function () {
    // Test with a small source video dimension
    $sourceDimension = new Dimension(640, 360); // 360p source
    
    // Try to apply 4K conversion (should be constrained)
    $resolution4K = new Resolution4K();
    $targetDimension = $resolution4K->getDimension();
    
    // The conversion dimension should be valid
    expect($targetDimension->getWidth())->toBe(3840)
        ->and($targetDimension->getHeight())->toBe(2160);
    
    // Check if it would scale up
    expect($resolution4K->wouldScaleUp($sourceDimension))->toBeTrue();
});

it('all conversions have proper bitrate settings', function () {
    $resolutions = [
        new Resolution144p(),
        new Resolution240p(),
        new Resolution360p(),
        new Resolution480p(),
        new Resolution720p(),
        new Resolution1080p(),
        new Resolution1440p(),
        new Resolution4K(),
    ];
    
    foreach ($resolutions as $resolution) {
        expect($resolution->getTargetBitrate())->toBeInt()
            ->and($resolution->getTargetBitrate())->toBeGreaterThan(0)
            ->and($resolution->getName())->toBeString()
            ->and($resolution->getDimension())->toBeInstanceOf(Dimension::class);
    }
});

it('conversions are properly ordered by quality', function () {
    $resolutions = [
        new Resolution144p(),
        new Resolution240p(), 
        new Resolution360p(),
        new Resolution480p(),
        new Resolution720p(),
        new Resolution1080p(),
        new Resolution1440p(),
        new Resolution4K(),
    ];
    
    $previousPixelCount = 0;
    
    foreach ($resolutions as $resolution) {
        $currentPixelCount = $resolution->getDimension()->getPixelCount();
        
        expect($currentPixelCount)->toBeGreaterThan($previousPixelCount);
        
        $previousPixelCount = $currentPixelCount;
    }
});