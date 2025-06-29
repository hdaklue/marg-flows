<?php

declare(strict_types=1);

namespace App\Actions\EditorJs;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class OptimizeEditorJsImage
{
    use AsAction;

    public function handle()
    {
        Log::info('OptimizeEditorJsImage');
    }
}
