<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Role\ManagesParticipants;
use App\Contracts\HasStaticType;
use App\Contracts\Role\RoleableEntity;
use App\Contracts\Tenant\BelongsToTenantContract;
use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Page extends Model implements HasStaticType, RoleableEntity
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory, HasStaticTypeTrait, HasUlids, ManagesParticipants;

    protected $fillable = [
        'name',
        'blocks',
    ];

    public function pageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function getTenant(): Tenant
    {
        if ($this->pageable instanceof BelongsToTenantContract) {
            return $this->pageable->getTenant();
        }
        throw new Exception("Can't resolve tenant of {static::class}");
    }

    public function casts(): array
    {
        return [
            'blocks' => 'json',
        ];
    }
}
