<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Document;
use App\Models\Flow;
use App\Services\Recency\RecencyService;
use App\Services\Recency\RecentableCollection;
use Hdaklue\NapTab\Enums\Shadow;
use Hdaklue\NapTab\Enums\TabBorderRadius;
use Hdaklue\NapTab\Enums\TabStyle;
use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\Services\NapTabConfig;
use Hdaklue\NapTab\UI\Tab;
use Illuminate\Support\HtmlString;
use Livewire\Component;

final class RecentInteractionsTabs extends NapTab
{
    /**
     * Get the NapTab configuration instance.
     */
    public function config(): NapTabConfig
    {
        return NapTabConfig::create()
            ->shadow(Shadow::None)
            ->radius(TabBorderRadius::None)
            ->navModalOnMobile(true)
            ->style(TabStyle::Minimal);
    }

    protected function tabs(): array
    {
        return [
            // Controller method approach (recommended for dynamic content)
            // Tab::make('all')->livewire(RecentableList::class, ['recents' => $this->getData()]),
            Tab::make('documents')->icon(
                'clipboard-document-list',
            )->livewire(RecentableList::class, [
                'recents' => $this->getData()->whereTypeIs((new Document())->getRecentType()),
            ]),
            Tab::make('flows')->icon('rectangle-stack')->livewire(RecentableList::class, [
                'recents' => $this->getData()->whereTypeIs((new Flow())->getRecentType()),
            ]),
            // Tab::make('workflows')->content(new HtmlString('Workflows')),
            // Tab::make('analytics'),
            // Livewire component integration
            // Tab::make('settings', 'Settings')->icon(
            //     'cog-6-tooth',
            // )->livewire(\App\Livewire\UserSettings::class, ['userId' => auth()->id()]),
        ];
    }

    protected function getData(): RecentableCollection
    {
        return RecencyService::forUser(filamentUser(), 20);
    }
}
