<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait Archiveable
{
    public function archive()
    {
        $this->archived_at = now();
        $this->save();
    }

    public function unArchive()
    {
        $this->archived_at = null;
        $this->save();
    }

    public function isArchived()
    {
        return ! empty($this->archived_at);
    }

    #[Scope]
    protected function archived(Builder $builder): Builder
    {
        return $builder->whereNotNull('archived_at');
    }

    #[Scope]
    protected function notAcriched(Builder $builder): Builder
    {
        return $builder->whereNull('archived_at');
    }
}
