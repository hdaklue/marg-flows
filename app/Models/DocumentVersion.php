<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use App\Services\Document\DocumentVersionPolisher;
use App\Support\Formatter\Concers\HasPolisher;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $with = ['document', 'creator'];

    protected $casts = [
        'content' => 'array',
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    protected function shortId(): Attribute
    {
        return Attribute::make(get: fn() => DocumentVersionPolisher::shortKey($this->getKey()));

        // return Attribute::make(get: fn() => substr($this->getKey(), -6));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
