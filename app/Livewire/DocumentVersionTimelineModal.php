<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\DocumentVersion;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use LivewireUI\Modal\ModalComponent;
use Log;

final class DocumentVersionTimelineModal extends ModalComponent
{
    public string $documentId;

    public ?string $currentEditingVersion = null;

    public bool $isPolling = false;

    public int $pollIntervalMs = 60000; // 60 seconds

    public ?string $lastCheckedAt = null;

    public static function closeModalOnClickAway(): bool
    {
        return false;
    }

    public static function closeModalOnEscape(): bool
    {
        return true;
    }

    public static function dispatchCloseEvent(): bool
    {
        return true;
    }

    public function mount(string $documentId, ?string $currentEditingVersion = null): void
    {
        $this->documentId = $documentId;
        $this->currentEditingVersion = $currentEditingVersion;
        $this->lastCheckedAt = now()->toISOString();

        // Notify parent component that version history modal was opened
        $this->dispatch('version-history-opened');
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
    public function dummyVersions(): array
    {
        return [
            [
                'id' => 'v-001',
                'created_at' => now()->toISOString(),
                'is_current_version' => true,
            ],
            [
                'id' => 'v-002',
                'created_at' => now()->subMinutes(15)->toISOString(),
                'is_current_version' => false,
            ],
            [
                'id' => 'v-003',
                'created_at' => now()->subHours(2)->toISOString(),
                'is_current_version' => false,
            ],
            [
                'id' => 'v-004',
                'created_at' => now()->subDays(1)->toISOString(),
                'is_current_version' => false,
            ],
            [
                'id' => 'v-005',
                'created_at' => now()->subDays(3)->toISOString(),
                'is_current_version' => false,
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.document-version-timeline-modal');
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
