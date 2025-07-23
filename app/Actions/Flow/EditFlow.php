<?php

declare(strict_types=1);

namespace App\Actions\Flow;

use App\Models\Flow;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

final class EditFlow
{
    use AsAction;

    public function handle(Flow $flow, string $title, string $description, Carbon $due_date)
    {

        $flow->update([
            'title' => $title,
            'description' => $description,
            'due_date' => $due_date,
        ]);
    }
}
