<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\DocumentVersion;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Log;

final class DocumentVersionTimeline extends Component
{
    public string $documentId;

    public ?string $currentEditingVersion = null;

    public bool $isPolling = false;

    public int $pollIntervalMs = 60000; // 60 seconds

    public ?string $lastCheckedAt = null;

    public function mount(string $documentId, ?string $currentEditingVersion = null): void
    {
        $this->documentId = $documentId;
        $this->currentEditingVersion = $currentEditingVersion;
        $this->lastCheckedAt = now()->toISOString();
    }

    #[On('start-polling')]
    public function startPolling(): void
    {
        $this->isPolling = true;
        Log::info('Document version polling started', ['document_id' => $this->documentId]);
    }

    #[On('stop-polling')]
    public function stopPolling(): void
    {
        $this->isPolling = false;
        Log::info('Document version polling stopped', ['document_id' => $this->documentId]);
    }

    #[On('version-selected')]
    public function handleVersionSelection(string $versionId): void
    {
        $this->currentEditingVersion = $versionId;
        $this->dispatch('document-version-changed', versionId: $versionId);
    }

    /**
     * Check for new versions since last check - called by Alpine.js polling.
     */
    public function checkForNewVersions(): array
    {
        try {
            $lastCheck = $this->lastCheckedAt ? Carbon::parse($this->lastCheckedAt) : now()->subHour();

            $newVersions = DocumentVersion::where('document_id', $this->documentId)
                ->where('created_at', '>', $lastCheck)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($version) => $this->transformVersion($version))
                ->toArray();

            $this->lastCheckedAt = now()->toISOString();

            if (! empty($newVersions)) {
                Log::info('New document versions found', [
                    'document_id' => $this->documentId,
                    'count' => count($newVersions),
                ]);

                // Dispatch event to update timeline
                $this->dispatch('new-versions-found', versions: $newVersions);
            }

            return $newVersions;
        } catch (Exception $e) {
            Log::error('Error checking for new versions', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function handleNewVersion(array $data): void
    {
        // Refresh the versions when a new version is created
        $this->dispatch('$refresh');

        // Optionally auto-select the new version if it's from the current user
        if ($data['auto_select'] ?? false) {
            $this->currentEditingVersion = $data['version_id'];
            $this->dispatch('document-version-changed', versionId: $data['version_id']);
        }
    }

    public function handleVersionUpdate(array $data): void
    {
        // Refresh specific version data
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function versions(): Collection
    {
        return DocumentVersion::where('document_id', $this->documentId)
            ->orderByDesc('created_at')
            ->limit(20) // Show last 20 versions for performance
            ->get()
            ->map(fn ($version) => $this->transformVersion($version));
    }

    public function render(): View
    {
        return view('livewire.document-version-timeline');
    }

    /**
     * Transform a DocumentVersion model into view data.
     */
    private function transformVersion(DocumentVersion $version): array
    {
        $user = User::find($version->created_by);

        return [
            'id' => $version->id,
            'content_preview' => $this->generatePreview($version->content),
            'created_at' => $version->created_at->toISOString(),
            'author' => $user?->name ?? 'Unknown User',
            'author_avatar' => $user?->avatar_url ?? null,
            'word_count' => $this->calculateWordCount($version->content),
            'char_count' => $this->calculateCharCount($version->content),
            'is_auto_save' => false, // Add logic to determine if auto-save
            'block_count' => count($version->content['blocks'] ?? []),
        ];
    }

    /**
     * Generate a preview from EditorJS content blocks.
     */
    private function generatePreview(array $content): string
    {
        if (empty($content['blocks'])) {
            return 'Empty document';
        }

        $textBlocks = collect($content['blocks'])
            ->filter(fn ($block) => in_array($block['type'] ?? '', ['paragraph', 'header']))
            ->map(function ($block) {
                $text = $block['data']['text'] ?? '';

                return strip_tags($text); // Remove any HTML tags
            })
            ->filter()
            ->take(2);

        if ($textBlocks->isEmpty()) {
            $blockTypes = collect($content['blocks'])->pluck('type')->unique();

            return count($content['blocks']) . ' blocks (' . $blockTypes->join(', ') . ')';
        }

        $preview = $textBlocks->implode(' ');

        return strlen($preview) > 100 ? substr($preview, 0, 100) . '...' : $preview;
    }

    /**
     * Calculate word count from EditorJS content.
     */
    private function calculateWordCount(array $content): int
    {
        if (empty($content['blocks'])) {
            return 0;
        }

        $wordCount = collect($content['blocks'])
            ->filter(fn ($block) => in_array($block['type'] ?? '', ['paragraph', 'header']))
            ->map(function ($block) {
                $text = $block['data']['text'] ?? '';
                $cleanText = strip_tags($text);

                return str_word_count($cleanText);
            })
            ->sum();

        return $wordCount;
    }

    /**
     * Calculate character count from EditorJS content.
     */
    private function calculateCharCount(array $content): int
    {
        if (empty($content['blocks'])) {
            return 0;
        }

        $charCount = collect($content['blocks'])
            ->filter(fn ($block) => in_array($block['type'] ?? '', ['paragraph', 'header']))
            ->map(function ($block) {
                $text = $block['data']['text'] ?? '';
                $cleanText = strip_tags($text);

                return strlen($cleanText);
            })
            ->sum();

        return $charCount;
    }
}
