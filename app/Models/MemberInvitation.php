<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberInvitation extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'role_data',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeSentBy(Builder $query, User $user): Builder
    {
        return $query->where('sender_id', '=', $user->id);
    }

    protected function casts(): array
    {
        return [

            'role_data' => 'array',
        ];
    }
}
