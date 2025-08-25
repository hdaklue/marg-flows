<?php

declare(strict_types=1);

use App\Services\Video\Conversions\Conversion144p;
use App\Services\Video\Conversions\Conversion240p;
use App\Services\Video\Conversions\Conversion360p;
use App\Services\Video\Conversions\Conversion480p;
use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Conversions\Conversion1080p;
use App\Services\Video\Conversions\Conversion1440p;
use App\Services\Video\Conversions\Conversion4K;
use App\Services\Video\ValueObjects\Dimension;

it('creates 144p conversion with correct dimensions', function () {
    $conversion = new Conversion144p();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(256)
        ->and($conversion->getDimension()->getHeight())->toBe(144)
        ->and($conversion->getName())->toBe('144p')
        ->and($conversion->getTargetBitrate())->toBe(200);
});

it('creates 240p conversion with correct dimensions', function () {
    $conversion = new Conversion240p();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(426)
        ->and($conversion->getDimension()->getHeight())->toBe(240)
        ->and($conversion->getName())->toBe('240p')
        ->and($conversion->getTargetBitrate())->toBe(400);
});

it('creates 360p conversion with correct dimensions', function () {
    $conversion = new Conversion360p();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(640)
        ->and($conversion->getDimension()->getHeight())->toBe(360)
        ->and($conversion->getName())->toBe('360p')
        ->and($conversion->getTargetBitrate())->toBe(800);
});

it('creates 480p conversion with correct dimensions', function () {
    $conversion = new Conversion480p();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(854)
        ->and($conversion->getDimension()->getHeight())->toBe(480)
        ->and($conversion->getName())->toBe('480p')
        ->and($conversion->getTargetBitrate())->toBe(1200);
});

it('creates 720p conversion with correct dimensions', function () {
    $conversion = new Conversion720p();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(1280)
        ->and($conversion->getDimension()->getHeight())->toBe(720)
        ->and($conversion->getName())->toBe('720p')
        ->and($conversion->getTargetBitrate())->toBe(2500);
});

it('creates 1080p conversion with correct dimensions', function () {
    $conversion = new Conversion1080p();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(1920)
        ->and($conversion->getDimension()->getHeight())->toBe(1080)
        ->and($conversion->getName())->toBe('1080p')
        ->and($conversion->getTargetBitrate())->toBe(5000);
});

it('creates 1440p conversion with correct dimensions', function () {
    $conversion = new Conversion1440p();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(2560)
        ->and($conversion->getDimension()->getHeight())->toBe(1440)
        ->and($conversion->getName())->toBe('1440p')
        ->and($conversion->getTargetBitrate())->toBe(6000);
});

it('creates 4K conversion with correct dimensions', function () {
    $conversion = new Conversion4K();
    
    expect($conversion->getDimension())->toBeInstanceOf(Dimension::class)
        ->and($conversion->getDimension()->getWidth())->toBe(3840)
        ->and($conversion->getDimension()->getHeight())->toBe(2160)
        ->and($conversion->getName())->toBe('4K')
        ->and($conversion->getTargetBitrate())->toBe(8000);
});

it('conversion respects scale-up constraints', function () {
    // Test with a small source video dimension
    $sourceDimension = new Dimension(640, 360); // 360p source
    
    // Try to apply 4K conversion (should be constrained)
    $conversion4K = new Conversion4K();
    $targetDimension = $conversion4K->getDimension();
    
    // The conversion dimension should be valid
    expect($targetDimension->getWidth())->toBe(3840)
        ->and($targetDimension->getHeight())->toBe(2160);
    
    // Check if it would scale up
    expect($conversion4K->wouldScaleUp($sourceDimension))->toBeTrue();
});

it('all conversions have proper bitrate settings', function () {
    $conversions = [
        new Conversion144p(),
        new Conversion240p(),
        new Conversion360p(),
        new Conversion480p(),
        new Conversion720p(),
        new Conversion1080p(),
        new Conversion1440p(),
        new Conversion4K(),
    ];
    
    foreach ($conversions as $conversion) {
        expect($conversion->getTargetBitrate())->toBeInt()
            ->and($conversion->getTargetBitrate())->toBeGreaterThan(0)
            ->and($conversion->getName())->toBeString()
            ->and($conversion->getDimension())->toBeInstanceOf(Dimension::class);
    }
});

it('conversions are properly ordered by quality', function () {
    $conversions = [
        new Conversion144p(),
        new Conversion240p(), 
        new Conversion360p(),
        new Conversion480p(),
        new Conversion720p(),
        new Conversion1080p(),
        new Conversion1440p(),
        new Conversion4K(),
    ];
    
    $previousPixelCount = 0;
    
    foreach ($conversions as $conversion) {
        $currentPixelCount = $conversion->getDimension()->getPixelCount();
        
        expect($currentPixelCount)->toBeGreaterThan($previousPixelCount);
        
        $previousPixelCount = $currentPixelCount;
    }
});