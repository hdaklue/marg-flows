<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberInvitation extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'email',
        'accepted_at',
        'role_data',
        'expires_at',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function expired(): bool
    {
        return now()->gt($this->expires_at);
    }

    public function accepted(): bool
    {
        return $this->accepted_at !== null;
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'role_data' => 'array',
        ];
    }
}
