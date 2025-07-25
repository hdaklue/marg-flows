<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Filament\Resources\PageResource;
use App\Models\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection $participants
 * @property-read array $participantsArray
 * @property-read array $userPermissions
 */
#[Lazy(false)]
final class PageCard extends Component
{
    #[Locked]
    public string $pageId;

    #[Locked]
    public string $createdAt;

    public Page $page;

    public string $name;

    public ?string $updatedAt;

    #[Locked]
    public string $pageableId;

    public function mount(string $pageId, string $pageableId): void
    {
        $this->page = Page::where('id', $pageId)->firstOrFail();
        $this->createdAt = toUserDateTime($this->page->created_at, filamentUser());
        $this->updatedAt = toUserDateTime($this->page->updated_at, filamentUser());

    }

    #[Computed]
    public function participants(): Collection
    {
        return $this->page->getParticipants();
    }

    #[Computed]
    public function participantsArray(): array
    {

        return $this->participants->pluck('model')->map(fn ($item) => ['name' => $item->getAttribute('name'), 'avatar' => $item->getAttribute('avatar')])->toArray();
    }

    #[Computed(true)]
    public function userPermissions(): array
    {
        return [
            'canManageMembers' => filamentUser()->can('manageMembers', $this->page),
            'canEdit' => filamentUser()->can('update', $this->page),
        ];
    }

    public function updateTitle(string $title): void
    {
        $this->authorize('update', $this->page);

        $this->page->update(['name' => $title]);

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
        $this->redirect(PageResource::getUrl('view', ['record' => $this->page->getKey()]));
        $this->dispatch('open-page', $this->pageId);
    }

    public function render()
    {
        return view('livewire.components.page-card');
    }
}
