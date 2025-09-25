<?php

declare(strict_types=1);

namespace App\Services\Recency\Actions;

use App\Models\User;
use App\Services\Recency\Contracts\Recentable;
use App\Services\Recency\RecencyService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordRecency
{
    use AsAction;

    public function handle(Authenticatable|User $authenticatable, Recentable|Model $recentableItem): void
    {
        RecencyService::tap($authenticatable, $recentableItem);
    }
}
