<?php

namespace App\Livewire\Dashboard;

use App\Models\Recent;
use App\Services\Recency\RecencyService;
use App\Services\Recency\RecentableCollection;
use Closure;
use Hdaklue\NapTab\Enums\Shadow;
use Hdaklue\NapTab\Enums\TabBorderRadius;
use Hdaklue\NapTab\Enums\TabStyle;
use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\Services\NapTabConfig;
use Hdaklue\NapTab\UI\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Component;

class RecentInteractionsTabs extends NapTab
{
    protected function tabs(): array
    {
        return [
            // Controller method approach (recommended for dynamic content)
            Tab::make('all')->livewire(RecentableList::class, ['recents' => $this->getData()]),
            Tab::make('documents')->livewire(RecentableList::class, ['recents' =>
                $this->getData()]),
            // Tab::make('workflows')->content(new HtmlString('Workflows')),
            // Tab::make('analytics'),
            // Livewire component integration
            // Tab::make('settings', 'Settings')->icon(
            //     'cog-6-tooth',
            // )->livewire(\App\Livewire\UserSettings::class, ['userId' => auth()->id()]),
        ];
    }

    /**
     * Get the NapTab configuration instance
     */
    public function config(): NapTabConfig
    {
        return NapTabConfig::create()
            ->shadow(Shadow::None)
            ->radius(TabBorderRadius::None)
            ->navModalOnMobile(true)
            ->style(TabStyle::Minimal);
    }

    protected function getData(): RecentableCollection
    {
        return RecencyService::forUser(filamentUser(), 20);
    }
}
