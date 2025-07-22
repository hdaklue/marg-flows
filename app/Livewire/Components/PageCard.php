<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Lazy(false)]
final class PageCard extends Component
{
    #[Locked]
    public string $pageId;

    #[Locked]
    public string $createdAt;

    public Page $page;

    public string $name;

    public function mount(string $pageId): void
    {
        $this->page = Page::where('id', $pageId)->firstOrFail();
        $this->createdAt = toUserDateTime($this->page->created_at, filamentUser());
    }

    #[Computed]
    public function participants(): Collection
    {
        return $this->page->getParticipants();
    }

    #[Computed]
    public function participantsArray(): array
    {

        return $this->participants->pluck('model')->map(fn ($item) => ['name' => $item->name, 'avatar' => $item->avatar])->toArray();
    }

    #[Computed(true)]
    public function userPermissions(): array
    {
        return [
            'canManageMembers' => filamentUser()->can('manageMembers', $this->page),
        ];
    }

    public function updateTitle(string $title): void
    {
        // Simulate network delay for now
        sleep(1);

        // TODO: Implement actual update logic

        // Update the name property
        $this->name = $title;

        // Emit event to parent to refresh if needed
        $this->dispatch('page-updated', $this->pageId);
    }

    #[On('roleable-entity:members-updated.{pageId}')]
    public function realoadMembers(): void
    {
        unset($this->participantsArray);
    }

    public function openPage(): void
    {
        $this->js("console.log('openinf')");
        // TODO: Implement page opening logic
        $this->dispatch('open-page', $this->pageId);
    }

    public function render()
    {
        return view('livewire.components.page-card');
    }
}
