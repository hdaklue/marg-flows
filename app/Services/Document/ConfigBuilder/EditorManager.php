<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder;

use App\Services\Document\ConfigBuilder\Builders\Advanced;
use App\Services\Document\ConfigBuilder\Builders\Base;
use App\Services\Document\ConfigBuilder\Builders\Simple;
use App\Services\Document\ConfigBuilder\Builders\Ultimate;
use Illuminate\Support\Manager;

final class EditorManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'base';
    }

    public function base(): Base
    {
        return new Base();
    }

    public function simple(): Simple
    {
        return new Simple();
    }

    public function advanced(): Advanced
    {
        return new Advanced();
    }

    public function ultimate(): Ultimate
    {
        return new Ultimate();
    }
}
