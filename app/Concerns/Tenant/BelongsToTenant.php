<?php

namespace App\Concerns\Tenant;

use App\Models\Tenant;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(static function ($model) {

            if ($model->tenant_id) {
                return;
            }
            throw_if(! auth()->user() && ! $model->tenant_id, new Exception('Cannot resolve team id for creating model'));

            throw_unless(auth()->user()->active_tenant_id, new Exception('Cannot resolve team id for creating model'));

            $model->tenant_id = Filament::getTenant()->id();
        });

    }

    public function tenant(): BelongsTo
    {
        return $this->belongsto(Tenant::class);
    }

    #[Scope]
    protected function byTenant(Builder $builder, string $tenant_id)
    {
        return $builder->where('tenant_id', $tenant_id);
    }
}
