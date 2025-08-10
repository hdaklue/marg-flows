<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder;

use App\Services\Document\ConfigBuilder\Blocks\Alert;
use App\Services\Document\ConfigBuilder\Blocks\Header;
use App\Services\Document\ConfigBuilder\Blocks\Hyperlink;
use App\Services\Document\ConfigBuilder\Blocks\Images;
use App\Services\Document\ConfigBuilder\Blocks\NestedList;
use App\Services\Document\ConfigBuilder\Blocks\Paragraph;
use App\Services\Document\ConfigBuilder\Blocks\Table;
use App\Services\Document\ConfigBuilder\Blocks\VideoEmbed;
use App\Services\Document\ConfigBuilder\Blocks\VideoUpload;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;
use Illuminate\Support\Manager;

final class ConfigManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'paragraph';
    }

    public function paragraph(): DocumentBlockConfigContract
    {
        return new Paragraph;
    }

    public function header(): DocumentBlockConfigContract
    {

        return new Header;
    }

    public function images(): DocumentBlockConfigContract
    {
        return new Images;
    }

    public function table(): DocumentBlockConfigContract
    {
        return new Table;
    }

    public function videoEmbed(): DocumentBlockConfigContract
    {
        return new VideoEmbed;
    }

    public function nestedList(): DocumentBlockConfigContract
    {
        return new NestedList;
    }

    public function alert(): DocumentBlockConfigContract
    {
        return new Alert;
    }

    public function hyperlink(): DocumentBlockConfigContract
    {
        return new Hyperlink;
    }

    public function videoUpload(): DocumentBlockConfigContract
    {
        return new VideoUpload;
    }
}
