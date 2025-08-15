<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\DTOs\ChunkData;
use App\Services\Upload\DTOs\ProgressData;
use App\Services\Upload\Strategies\Progress\HttpResponseProgressStrategy;
use App\Services\Upload\Strategies\Progress\LogProgressStrategy;
use App\Services\Upload\Strategies\Progress\SimpleProgressStrategy;
use App\Services\Upload\Strategies\Progress\WebSocketProgressStrategy;
use Illuminate\Support\Manager;

final class UploadSessionManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('upload.session.default', 'http');
    }

    /**
     * Create the HTTP response driver.
     */
    public function createHttpDriver(): UploadSessionService
    {
        return new UploadSessionService(
            new HttpResponseProgressStrategy()
        );
    }

    /**
     * Create the WebSocket driver.
     */
    public function createWebsocketDriver(): UploadSessionService
    {
        return new UploadSessionService(
            new WebSocketProgressStrategy()
        );
    }

    /**
     * Create the log driver.
     */
    public function createLogDriver(): UploadSessionService
    {
        return new UploadSessionService(
            new LogProgressStrategy()
        );
    }

    /**
     * Create the Redis driver.
     */
    public function createRedisDriver(): UploadSessionService
    {
        return new UploadSessionService(
            new SimpleProgressStrategy()
        );
    }
}