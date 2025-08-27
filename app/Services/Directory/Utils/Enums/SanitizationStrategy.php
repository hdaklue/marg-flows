<?php

declare(strict_types=1);

namespace App\Services\Directory\Utils\Enums;

use App\Services\Directory\Utils\Strategies\HashedStrategy;
use App\Services\Directory\Utils\Strategies\SlugStrategy;
use App\Services\Directory\Utils\Strategies\SnakeStrategy;
use App\Services\Directory\Utils\Strategies\TimestampStrategy;

/**
 * Enumeration of available sanitization strategies.
 * 
 * Provides type-safe access to sanitization strategy classes
 * with better IDE support and preventing typos.
 */
enum SanitizationStrategy: string
{
    case HASHED = HashedStrategy::class;
    case SNAKE = SnakeStrategy::class;
    case SLUG = SlugStrategy::class;
    case TIMESTAMP = TimestampStrategy::class;
}