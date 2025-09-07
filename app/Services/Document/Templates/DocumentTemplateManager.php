<?php

declare(strict_types=1);

namespace App\Services\Document\Templates;

use App\Services\Document\Contracts\DocumentTemplateContract;
use Exception;
use Illuminate\Support\Manager;

final class DocumentTemplateManager extends Manager
{
    public function getDefaultDriver(): DocumentTemplateContract
    {

        throw new Exception('Select a Template type');
    }

    public function general()
    {
        return General::make();
    }
}
