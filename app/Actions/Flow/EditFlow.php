<?php

declare(strict_types=1);

namespace App\Actions\Flow;

use App\Models\Flow;
use Lorisleiva\Actions\Concerns\AsAction;

final class EditFlow
{
    use AsAction;

    public function handle(Flow $flow, string $title, string $description)
    {
        $flow->update([
            'title' => $title,
            'description' => $description,
        ]);
    }
}
