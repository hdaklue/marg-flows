<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Flow;
use App\Models\User;
use App\Services\Document\DocumentVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->versionService = new DocumentVersionService;
    $this->user = User::factory()->create();
    $this->flow = Flow::factory()->create();
    $this->document = Document::factory()
        ->for($this->user, 'creator')
        ->for($this->flow, 'documentable')
        ->create();
});

describe('DocumentVersionService createInitialVersion', function () {
    it('creates initial version for a document', function () {
        $version = $this->versionService->createInitialVersion($this->document, $this->user);

        expect($version)->toBeInstanceOf(DocumentVersion::class)
            ->and($version->document_id)->toBe($this->document->id)
            ->and($version->created_by)->toBe($this->user->id)
            ->and($version->content)->toBe($this->document->blocks);

        $this->assertDatabaseHas('document_versions', [
            'document_id' => $this->document->id,
            'created_by' => $this->user->id,
        ]);
    });

    it('sets document current version to initial version', function () {
        $version = $this->versionService->createInitialVersion($this->document, $this->user);

        $this->document->refresh();
        expect($this->document->current_version_id)->toBe($version->id);
    });

    it('stores content from document blocks', function () {
        $blocks = [
            'time' => now()->timestamp,
            'blocks' => [
                [
                    'id' => '123',
                    'type' => 'paragraph',
                    'data' => ['text' => 'Initial content'],
                ],
            ],
            'version' => '2.28.2',
        ];

        $this->document->update(['blocks' => $blocks]);

        $version = $this->versionService->createInitialVersion($this->document, $this->user);

        expect($version->content)->toBe($blocks);
    });

    it('creates version with current timestamp', function () {
        $beforeTime = now();
        $version = $this->versionService->createInitialVersion($this->document, $this->user);
        $afterTime = now();

        expect($version->created_at)->toBeBetween($beforeTime, $afterTime);
    });

    it('associates version with correct document and creator', function () {
        $version = $this->versionService->createInitialVersion($this->document, $this->user);

        expect($version->document)->toBeInstanceOf(Document::class)
            ->and($version->document->id)->toBe($this->document->id)
            ->and($version->creator)->toBeInstanceOf(User::class)
            ->and($version->creator->id)->toBe($this->user->id);
    });
});

describe('DocumentVersionService createNewVersion', function () {
    beforeEach(function () {
        $this->initialVersion = $this->versionService->createInitialVersion($this->document, $this->user);
    });

    it('creates new version with array content', function () {
        $newContent = [
            'time' => now()->timestamp,
            'blocks' => [
                [
                    'id' => '456',
                    'type' => 'header',
                    'data' => ['text' => 'Updated Header', 'level' => 2],
                ],
            ],
        ];

        $version = $this->versionService->createNewVersion($this->document, $newContent, $this->user);

        expect($version)->toBeInstanceOf(DocumentVersion::class)
            ->and($version->content)->toBe($newContent)
            ->and($version->document_id)->toBe($this->document->id)
            ->and($version->created_by)->toBe($this->user->id);
    });

    it('creates new version with string content', function () {
        $stringContent = '{"type": "string", "content": "test"}';

        $version = $this->versionService->createNewVersion($this->document, $stringContent, $this->user);

        expect($version->content)->toBe($stringContent);
    });

    it('updates document current version to new version', function () {
        $newContent = ['updated' => 'content'];

        $version = $this->versionService->createNewVersion($this->document, $newContent, $this->user);

        $this->document->refresh();
        expect($this->document->current_version_id)->toBe($version->id)
            ->and($this->document->current_version_id)->not->toBe($this->initialVersion->id);
    });

    it('creates version from different user', function () {
        $otherUser = User::factory()->create();
        $newContent = ['author' => 'other_user'];

        $version = $this->versionService->createNewVersion($this->document, $newContent, $otherUser);

        expect($version->created_by)->toBe($otherUser->id);
    });

    it('persists new version to database', function () {
        $newContent = ['database' => 'test'];

        $version = $this->versionService->createNewVersion($this->document, $newContent, $this->user);

        $this->assertDatabaseHas('document_versions', [
            'id' => $version->id,
            'document_id' => $this->document->id,
            'created_by' => $this->user->id,
        ]);
    });
});

describe('DocumentVersionService getVersionHistory', function () {
    beforeEach(function () {
        $this->versions = collect();

        // Create multiple versions
        $this->versions->push(
            $this->versionService->createInitialVersion($this->document, $this->user),
        );

        sleep(1); // Ensure different timestamps

        $this->versions->push(
            $this->versionService->createNewVersion($this->document, ['second' => 'version'], $this->user),
        );

        sleep(1);

        $this->versions->push(
            $this->versionService->createNewVersion($this->document, ['third' => 'version'], $this->user),
        );
    });

    it('retrieves all versions for a document', function () {
        $history = $this->versionService->getVersionHistory($this->document);

        expect($history)->toBeInstanceOf(Collection::class)
            ->and($history)->toHaveCount(3);
    });

    it('orders versions by created_at descending', function () {
        $history = $this->versionService->getVersionHistory($this->document);

        $timestamps = $history->pluck('created_at');

        expect($timestamps->first()->greaterThan($timestamps->last()))->toBeTrue();
    });

    it('loads creator relationship in version history', function () {
        $history = $this->versionService->getVersionHistory($this->document);

        $history->each(function ($version) {
            expect($version->relationLoaded('creator'))->toBeTrue()
                ->and($version->creator)->toBeInstanceOf(User::class);
        });
    });

    it('returns empty collection for document with no versions', function () {
        $emptyDocument = Document::factory()
            ->for($this->user, 'creator')
            ->for($this->flow, 'documentable')
            ->create();

        $history = $this->versionService->getVersionHistory($emptyDocument);

        expect($history)->toHaveCount(0);
    });

    it('includes all version content', function () {
        $history = $this->versionService->getVersionHistory($this->document);

        $contentValues = $history->pluck('content')->toArray();

        expect($contentValues)->toContain(['third' => 'version'])
            ->and($contentValues)->toContain(['second' => 'version']);
    });
});

describe('DocumentVersionService getVersion', function () {
    beforeEach(function () {
        $this->version = $this->versionService->createInitialVersion($this->document, $this->user);
    });

    it('retrieves version by ID', function () {
        $result = $this->versionService->getVersion($this->version->id);

        expect($result)->toBeInstanceOf(DocumentVersion::class)
            ->and($result->id)->toBe($this->version->id);
    });

    it('loads document and creator relationships', function () {
        $result = $this->versionService->getVersion($this->version->id);

        expect($result->relationLoaded('document'))->toBeTrue()
            ->and($result->relationLoaded('creator'))->toBeTrue()
            ->and($result->document)->toBeInstanceOf(Document::class)
            ->and($result->creator)->toBeInstanceOf(User::class);
    });

    it('returns null for non-existent version ID', function () {
        $result = $this->versionService->getVersion('non-existent-id');

        expect($result)->toBeNull();
    });

    it('returns correct version data', function () {
        $result = $this->versionService->getVersion($this->version->id);

        expect($result->document_id)->toBe($this->document->id)
            ->and($result->created_by)->toBe($this->user->id)
            ->and($result->content)->toBe($this->document->blocks);
    });
});

describe('DocumentVersionService rollbackToVersion', function () {
    beforeEach(function () {
        $this->initialVersion = $this->versionService->createInitialVersion($this->document, $this->user);

        $this->secondVersion = $this->versionService->createNewVersion(
            $this->document,
            ['second' => 'version'],
            $this->user,
        );

        $this->thirdVersion = $this->versionService->createNewVersion(
            $this->document,
            ['third' => 'version'],
            $this->user,
        );
    });

    it('creates new version with content from target version', function () {
        $rollbackVersion = $this->versionService->rollbackToVersion(
            $this->document,
            $this->initialVersion,
            $this->user,
        );

        expect($rollbackVersion)->toBeInstanceOf(DocumentVersion::class)
            ->and($rollbackVersion->content)->toBe($this->initialVersion->content)
            ->and($rollbackVersion->id)->not->toBe($this->initialVersion->id); // New version, not the same
    });

    it('updates document current version to rollback version', function () {
        $rollbackVersion = $this->versionService->rollbackToVersion(
            $this->document,
            $this->secondVersion,
            $this->user,
        );

        $this->document->refresh();
        expect($this->document->current_version_id)->toBe($rollbackVersion->id);
    });

    it('allows rolling back to any previous version', function () {
        $rollbackVersion = $this->versionService->rollbackToVersion(
            $this->document,
            $this->initialVersion,
            $this->user,
        );

        expect($rollbackVersion->content)->toBe($this->initialVersion->content);
    });

    it('creates rollback version with specified user as creator', function () {
        $otherUser = User::factory()->create();

        $rollbackVersion = $this->versionService->rollbackToVersion(
            $this->document,
            $this->secondVersion,
            $otherUser,
        );

        expect($rollbackVersion->created_by)->toBe($otherUser->id);
    });

    it('persists rollback version to database', function () {
        $rollbackVersion = $this->versionService->rollbackToVersion(
            $this->document,
            $this->initialVersion,
            $this->user,
        );

        $this->assertDatabaseHas('document_versions', [
            'id' => $rollbackVersion->id,
            'document_id' => $this->document->id,
        ]);
    });
});

describe('DocumentVersionService compareVersions', function () {
    beforeEach(function () {
        $this->oldVersion = $this->versionService->createInitialVersion($this->document, $this->user);

        sleep(1); // Ensure different timestamp

        $this->newVersion = $this->versionService->createNewVersion(
            $this->document,
            ['updated' => 'content'],
            $this->user,
        );
    });

    it('returns comparison array with both versions', function () {
        $comparison = $this->versionService->compareVersions($this->oldVersion, $this->newVersion);

        expect($comparison)->toBeArray()
            ->and($comparison['old_version'])->toBe($this->oldVersion)
            ->and($comparison['new_version'])->toBe($this->newVersion);
    });

    it('includes content from both versions', function () {
        $comparison = $this->versionService->compareVersions($this->oldVersion, $this->newVersion);

        expect($comparison['old_content'])->toBe($this->oldVersion->content)
            ->and($comparison['new_content'])->toBe($this->newVersion->content);
    });

    it('calculates time difference between versions', function () {
        $comparison = $this->versionService->compareVersions($this->oldVersion, $this->newVersion);

        expect($comparison['created_at_diff'])->toBeString()
            ->and($comparison['created_at_diff'])->toContain('second'); // Should show time difference
    });

    it('handles same version comparison', function () {
        $comparison = $this->versionService->compareVersions($this->oldVersion, $this->oldVersion);

        expect($comparison['old_version'])->toBe($this->oldVersion)
            ->and($comparison['new_version'])->toBe($this->oldVersion)
            ->and($comparison['old_content'])->toBe($comparison['new_content']);
    });
});

describe('DocumentVersionService getLatestVersion', function () {
    beforeEach(function () {
        $this->firstVersion = $this->versionService->createInitialVersion($this->document, $this->user);

        sleep(1);

        $this->latestVersion = $this->versionService->createNewVersion(
            $this->document,
            ['latest' => 'content'],
            $this->user,
        );
    });

    it('retrieves the most recent version', function () {
        $latest = $this->versionService->getLatestVersion($this->document);

        expect($latest)->toBeInstanceOf(DocumentVersion::class)
            ->and($latest->id)->toBe($this->latestVersion->id);
    });

    it('loads creator relationship', function () {
        $latest = $this->versionService->getLatestVersion($this->document);

        expect($latest->relationLoaded('creator'))->toBeTrue()
            ->and($latest->creator)->toBeInstanceOf(User::class);
    });

    it('returns null for document with no versions', function () {
        $emptyDocument = Document::factory()
            ->for($this->user, 'creator')
            ->for($this->flow, 'documentable')
            ->create();

        $latest = $this->versionService->getLatestVersion($emptyDocument);

        expect($latest)->toBeNull();
    });

    it('returns correct latest version when multiple versions exist', function () {
        // Create one more version to ensure we get the latest
        sleep(1);
        $newestVersion = $this->versionService->createNewVersion(
            $this->document,
            ['newest' => 'content'],
            $this->user,
        );

        $latest = $this->versionService->getLatestVersion($this->document);

        expect($latest->id)->toBe($newestVersion->id)
            ->and($latest->content)->toBe(['newest' => 'content']);
    });
});

describe('DocumentVersionService pruneOldVersions', function () {
    beforeEach(function () {
        $this->versions = collect();

        // Create 15 versions
        $this->versions->push(
            $this->versionService->createInitialVersion($this->document, $this->user),
        );

        for ($i = 1; $i < 15; $i++) {
            sleep(1); // Ensure different timestamps
            $this->versions->push(
                $this->versionService->createNewVersion(
                    $this->document,
                    ['version' => $i],
                    $this->user,
                ),
            );
        }
    });

    it('deletes old versions keeping specified count', function () {
        $deletedCount = $this->versionService->pruneOldVersions($this->document, 5);

        expect($deletedCount)->toBe(10); // 15 - 5 = 10 deleted

        $remainingVersions = $this->versionService->getVersionHistory($this->document);
        expect($remainingVersions)->toHaveCount(5);
    });

    it('keeps the most recent versions', function () {
        $this->versionService->pruneOldVersions($this->document, 3);

        $remainingVersions = $this->versionService->getVersionHistory($this->document);

        // Should keep the last 3 versions
        expect($remainingVersions)->toHaveCount(3);

        $latestVersion = $remainingVersions->first();
        expect($latestVersion->content)->toBe(['version' => 14]); // Latest version
    });

    it('uses default keep count of 10', function () {
        $deletedCount = $this->versionService->pruneOldVersions($this->document);

        expect($deletedCount)->toBe(5); // 15 - 10 = 5 deleted

        $remainingVersions = $this->versionService->getVersionHistory($this->document);
        expect($remainingVersions)->toHaveCount(10);
    });

    it('handles case with fewer versions than keep count', function () {
        $smallDocument = Document::factory()
            ->for($this->user, 'creator')
            ->for($this->flow, 'documentable')
            ->create();

        $this->versionService->createInitialVersion($smallDocument, $this->user);
        $this->versionService->createNewVersion($smallDocument, ['second'], $this->user);

        $deletedCount = $this->versionService->pruneOldVersions($smallDocument, 10);

        expect($deletedCount)->toBe(0);

        $remainingVersions = $this->versionService->getVersionHistory($smallDocument);
        expect($remainingVersions)->toHaveCount(2);
    });

    it('returns correct count of deleted versions', function () {
        $deletedCount = $this->versionService->pruneOldVersions($this->document, 7);

        expect($deletedCount)->toBe(8); // 15 - 7 = 8 deleted
    });
});

describe('DocumentVersionService isCurrentVersion', function () {
    beforeEach(function () {
        $this->firstVersion = $this->versionService->createInitialVersion($this->document, $this->user);
        $this->secondVersion = $this->versionService->createNewVersion(
            $this->document,
            ['second' => 'version'],
            $this->user,
        );
    });

    it('returns true for current version', function () {
        $this->document->refresh();

        $isCurrent = $this->versionService->isCurrentVersion($this->secondVersion);

        expect($isCurrent)->toBeTrue();
    });

    it('returns false for non-current version', function () {
        $isCurrent = $this->versionService->isCurrentVersion($this->firstVersion);

        expect($isCurrent)->toBeFalse();
    });

    it('correctly identifies current version after rollback', function () {
        // Rollback to first version
        $rollbackVersion = $this->versionService->rollbackToVersion(
            $this->document,
            $this->firstVersion,
            $this->user,
        );

        $this->document->refresh();

        expect($this->versionService->isCurrentVersion($rollbackVersion))->toBeTrue()
            ->and($this->versionService->isCurrentVersion($this->firstVersion))->toBeFalse()
            ->and($this->versionService->isCurrentVersion($this->secondVersion))->toBeFalse();
    });

    it('handles version from different document', function () {
        $otherDocument = Document::factory()
            ->for($this->user, 'creator')
            ->for($this->flow, 'documentable')
            ->create();

        $otherVersion = $this->versionService->createInitialVersion($otherDocument, $this->user);

        $isCurrent = $this->versionService->isCurrentVersion($otherVersion);

        expect($isCurrent)->toBeTrue(); // Current for its own document
    });
});

describe('DocumentVersionService edge cases', function () {
    it('handles version creation with empty content', function () {
        $version = $this->versionService->createNewVersion($this->document, [], $this->user);

        expect($version->content)->toBe([]);
    });

    it('handles version creation with null content', function () {
        $version = $this->versionService->createNewVersion($this->document, null, $this->user);

        expect($version->content)->toBeNull();
    });

    it('handles comparison with versions having null content', function () {
        $versionWithNull = $this->versionService->createNewVersion($this->document, null, $this->user);
        $versionWithContent = $this->versionService->createNewVersion($this->document, ['test'], $this->user);

        $comparison = $this->versionService->compareVersions($versionWithNull, $versionWithContent);

        expect($comparison['old_content'])->toBeNull()
            ->and($comparison['new_content'])->toBe(['test']);
    });

    it('handles pruning when document has exactly the keep count', function () {
        $document = Document::factory()
            ->for($this->user, 'creator')
            ->for($this->flow, 'documentable')
            ->create();

        // Create exactly 5 versions
        $this->versionService->createInitialVersion($document, $this->user);
        for ($i = 1; $i < 5; $i++) {
            $this->versionService->createNewVersion($document, ['version' => $i], $this->user);
        }

        $deletedCount = $this->versionService->pruneOldVersions($document, 5);

        expect($deletedCount)->toBe(0);
        expect($this->versionService->getVersionHistory($document))->toHaveCount(5);
    });

    it('handles version history ordering with same timestamps', function () {
        // Create versions rapidly to potentially have same timestamps
        $version1 = $this->versionService->createInitialVersion($this->document, $this->user);
        $version2 = $this->versionService->createNewVersion($this->document, ['rapid1'], $this->user);
        $version3 = $this->versionService->createNewVersion($this->document, ['rapid2'], $this->user);

        $history = $this->versionService->getVersionHistory($this->document);

        expect($history)->toHaveCount(3);
        // Should still maintain some consistent ordering
        expect($history->pluck('id')->toArray())->toContain($version1->id, $version2->id, $version3->id);
    });
});
