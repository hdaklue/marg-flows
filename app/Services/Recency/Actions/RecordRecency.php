<?php

namespace App\Services\Recency\Actions;

use App\Models\User;
use App\Services\Recency\Contracts\Recentable;
use App\Services\Recency\RecencyService;
use Filament\Actions\Concerns\HasAction;
use Illuminate\Contracts\Auth\Authenticatable;
use Lorisleiva\Actions\Concerns\AsAction;

class RecordRecency
{
    use AsAction;

    public function handle(Authenticatable|User $authenticatable, Recentable $recentable)
    {
        RecencyService::tap($authenticatable, $recentable);
    }
}
