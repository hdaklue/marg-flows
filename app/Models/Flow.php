<?php

namespace App\Models;

use App\Concerns\Tenant\BelongsToTenant;
use App\Enums\FlowStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flow extends Model
{
    /** @use HasFactory<\Database\Factories\FlowFactory> */
    use BelongsToTenant, HasFactory, HasUlids;

    public $incrementing = false;

    protected $primaryKey = 'id'; // Make sure this matches your column

    protected $keyType = 'string';

    public function casts(): array
    {
        return [
            'settings' => 'array',
            // 'status' => FlowStatus::class,
            'due_date' => 'date',
            'start_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
