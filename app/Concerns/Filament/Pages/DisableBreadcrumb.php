<?php

declare(strict_types=1);

namespace App\Concerns\Filament\Pages;

trait DisableBreadcrumb
{
    public function getBreadcrumb(): string
    {
        return '';
    }
}
