<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class DocumentVersion extends Model
{
    use HasUlids, LivesInOriginalDB;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'content',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'content' => 'array',
        'created_at' => 'datetime',
    ];
}
