<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder;

use App\Services\Document\ConfigBuilder\DocumentBuilders\Advanced;
use App\Services\Document\ConfigBuilder\DocumentBuilders\Simple;
use App\Services\Document\ConfigBuilder\DocumentBuilders\Ultimate;
use Illuminate\Support\Manager;

final class DocumentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'simple';
    }

    public function simple(): Simple
    {
        return new Simple;
    }

    public function advanced(): Advanced
    {
        return new Advanced;
    }

    public function ultimate(): Ultimate
    {
        return new Ultimate;
    }
}
