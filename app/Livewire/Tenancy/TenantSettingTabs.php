<?php

declare(strict_types=1);

namespace App\Livewire\Tenancy;

use Hdaklue\NapTab\Enums\Direction;
use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\UI\Tab;
use Throwable;

final class TenantSettingTabs extends NapTab
{
    /**
     * @throws Throwable
     */
    public function tabs(): array
    {
        return [
            //            Tab::make('general')->label('General'),
            Tab::make('member')
                ->livewire(ManageMembersTab::class)
                ->label('Members'),
        ];
    }

    protected function direction(): Direction
    {
        return Direction::Aside;
    }
}
