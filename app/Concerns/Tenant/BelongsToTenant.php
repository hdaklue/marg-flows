<?php

declare(strict_types=1);

namespace App\Concerns\Tenant;

use App\Models\Tenant;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(static function ($model) {
            // Skip if tenant_id already set
            if ($model->tenant_id) {
                return;
            }

            $tenantId = static::resolveTenantId();

            throw_unless(
                $tenantId,
                new Exception('Cannot resolve tenant for creating model'),
            );

            $model->tenant_id = $tenantId;
        });
    }

    protected static function resolveTenantId(): int|string|null
    {
        // Try Filament first (if available)
        if (class_exists(Filament::class)) {
            $tenant = Filament::getTenant();
            if ($tenant) {
                return $tenant->getKey();
            }
        }

        // Fall back to authenticated user's active tenant
        if (auth()->check()) {
            return auth()->user()->active_tenant_id;
        }

        // Fall back to session or other methods
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }

        return null;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsto(Tenant::class);
    }

    /**
     * @return Tenant
     */
    public function getTenant(): Tenant
    {
        /** @var Tenant */
        return $this->tenant()->firstOrFail();
    }

    public function getTenantId(): string|int
    {
        return $this->getTenant()->getKey();
    }

    #[Scope]
    protected function scopeByTenant(Builder $builder, Tenant $tenant)
    {
        return $builder->where('tenant_id', $tenant->getKey());
    }
}
