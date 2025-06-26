<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stage extends Model
{
    use BelongsToTenant, HasUlids;

    protected $fillable = [
        'name',
        'color',
        'meta',
    ];

    public function stageable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
