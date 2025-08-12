<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInBusinessDB;
use App\Enums\Deliverable\DeliverableVersionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $deliverable_id
 * @property int $version_number
 * @property DeliverableVersionStatus $status
 * @property string|null $notes
 * @property array<array-key, mixed>|null $files
 * @property string $created_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion byStatus(\App\Enums\Deliverable\DeliverableVersionStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion drafts()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion latestVersions()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion needingRevision()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion orderByVersion(string $direction = 'desc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion submitted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereDeliverableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion whereVersionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliverableVersion withFiles()
 * @mixin \Eloquent
 */
final class DeliverableVersion extends Model
{
    use HasUlids, LivesInBusinessDB;

    protected $fillable = [
        'deliverable_id',
        'version_number',
        'status',
        'notes',
        'files',
        'created_by',
        'submitted_at',
    ];

    protected $attributes = [
        'status' => DeliverableVersionStatus::DRAFT->value,
    ];

    protected function casts(): array
    {
        return [
            'files' => 'array',
            'submitted_at' => 'datetime',
            'status' => DeliverableVersionStatus::class,
        ];
    }

    // Relationships
    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Status Management
    public function submit(): void
    {
        $this->update([
            'status' => DeliverableVersionStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function requestRevision(): void
    {
        $this->update([
            'status' => DeliverableVersionStatus::REVISION_NEEDED,
        ]);
    }

    public function backToDraft(): void
    {
        $this->update([
            'status' => DeliverableVersionStatus::DRAFT,
            'submitted_at' => null,
        ]);
    }

    public function canTransitionTo(DeliverableVersionStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function transitionTo(DeliverableVersionStatus $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $updateData = ['status' => $newStatus];

        // Set submitted_at when transitioning to submitted
        if ($newStatus === DeliverableVersionStatus::SUBMITTED) {
            $updateData['submitted_at'] = now();
        }

        // Clear submitted_at when going back to draft
        if ($newStatus === DeliverableVersionStatus::DRAFT) {
            $updateData['submitted_at'] = null;
        }

        $this->update($updateData);
        return true;
    }

    // File Management
    public function addFile(array $fileData): void
    {
        $files = $this->files ?? [];
        $files[] = $fileData;
        $this->update(['files' => $files]);
    }

    public function removeFile(string $fileId): void
    {
        $files = $this->files ?? [];
        $files = array_filter($files, fn($file) => ($file['id'] ?? null) !== $fileId);
        $this->update(['files' => array_values($files)]);
    }

    public function updateFile(string $fileId, array $newData): void
    {
        $files = $this->files ?? [];
        foreach ($files as &$file) {
            if (($file['id'] ?? null) === $fileId) {
                $file = array_merge($file, $newData);
                break;
            }
        }
        $this->update(['files' => $files]);
    }

    public function getFiles(): array
    {
        return $this->files ?? [];
    }

    public function hasFiles(): bool
    {
        return !empty($this->files);
    }

    public function getFileCount(): int
    {
        return count($this->files ?? []);
    }

    public function getFileByType(string $type): array
    {
        $files = $this->files ?? [];
        return array_filter($files, fn($file) => ($file['type'] ?? null) === $type);
    }

    // Status Helpers
    public function isDraft(): bool
    {
        return $this->status === DeliverableVersionStatus::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === DeliverableVersionStatus::SUBMITTED;
    }

    public function needsRevision(): bool
    {
        return $this->status === DeliverableVersionStatus::REVISION_NEEDED;
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function canBeSubmitted(): bool
    {
        return $this->isDraft() && $this->hasFiles();
    }

    // Version Helpers
    public function isLatest(): bool
    {
        return $this->version_number === $this->deliverable->versions()->max('version_number');
    }

    public function getPreviousVersion(): ?self
    {
        return $this->deliverable->versions()
                   ->where('version_number', '<', $this->version_number)
                   ->orderByDesc('version_number')
                   ->first();
    }

    public function getNextVersion(): ?self
    {
        return $this->deliverable->versions()
                   ->where('version_number', '>', $this->version_number)
                   ->orderBy('version_number')
                   ->first();
    }

    public function getVersionLabel(): string
    {
        return "v{$this->version_number}";
    }

    public function getFullVersionLabel(): string
    {
        return "{$this->deliverable->title} - {$this->getVersionLabel()}";
    }

    // Notes Management
    public function addNote(string $note): void
    {
        $currentNotes = $this->notes ? $this->notes . "\n\n" : '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] {$note}";
        
        $this->update(['notes' => $currentNotes . $newNote]);
    }

    public function hasNotes(): bool
    {
        return !empty(trim($this->notes ?? ''));
    }

    // Comparison
    public function getChangesFromPrevious(): array
    {
        $previous = $this->getPreviousVersion();
        
        if (!$previous) {
            return ['changes' => 'Initial version', 'files_added' => $this->getFileCount()];
        }

        $changes = [];
        $currentFiles = $this->getFiles();
        $previousFiles = $previous->getFiles();

        $changes['files_added'] = count($currentFiles) - count($previousFiles);
        $changes['has_new_files'] = count($currentFiles) > count($previousFiles);
        $changes['notes_changed'] = $this->notes !== $previous->notes;

        return $changes;
    }

    // Scopes
    public function scopeByStatus($query, DeliverableVersionStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', DeliverableVersionStatus::SUBMITTED);
    }

    public function scopeDrafts($query)
    {
        return $query->where('status', DeliverableVersionStatus::DRAFT);
    }

    public function scopeNeedingRevision($query)
    {
        return $query->where('status', DeliverableVersionStatus::REVISION_NEEDED);
    }

    public function scopeWithFiles($query)
    {
        return $query->whereNotNull('files')
                    ->where('files', '!=', '[]');
    }

    public function scopeLatestVersions($query)
    {
        return $query->whereIn('id', function ($subquery) {
            $subquery->select('id')
                    ->from('deliverable_versions as dv2')
                    ->whereColumn('dv2.deliverable_id', 'deliverable_versions.deliverable_id')
                    ->orderByDesc('version_number')
                    ->limit(1);
        });
    }

    public function scopeOrderByVersion($query, string $direction = 'desc')
    {
        return $query->orderBy('version_number', $direction);
    }
}