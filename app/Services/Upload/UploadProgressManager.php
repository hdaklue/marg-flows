<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\Strategies\Progress\SimpleProgressStrategy;

final class UploadProgressManager
{
    /**
     * Create a simple progress strategy that uses Redis for storage
     * and provides polling endpoints for progress tracking
     */
    public static function simple(): ProgressStrategyContract
    {
        return new SimpleProgressStrategy();
    }

    /**
     * Create a real-time progress strategy using WebSockets/SSE
     * (To be implemented)
     */
    public static function realtime(): ProgressStrategyContract
    {
        throw new \Exception('Realtime progress strategy not yet implemented');
    }
}