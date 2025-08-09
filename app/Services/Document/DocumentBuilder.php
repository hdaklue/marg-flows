<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Services\Document\DocumentBuilders\Advanced;
use App\Services\Document\DocumentBuilders\Simple;
use App\Services\Document\DocumentBuilders\Ultimate;
use Illuminate\Support\Manager;

final class DocumentBuilder extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'simple';
    }

    public function simple(): Simple
    {
        return new Simple($this->container->make(ConfigManager::class));
    }

    public function advanced(): Advanced
    {
        return new Advanced($this->container->make(ConfigManager::class));
    }

    public function ultimate(): Ultimate
    {
        return new Ultimate($this->container->make(ConfigManager::class));
    }
}