<?php

declare(strict_types=1);

use App\Livewire\DocumentVersionTimeline;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'active_tenant_id' => 'test-tenant-123',
    ]);
    $this->actingAs($this->user);

    // Create a document for testing
    $this->document = Document::factory()->create();

    // Create some test versions
    $this->versions = DocumentVersion::factory()
        ->count(3)
        ->create([
            'document_id' => $this->document->id,
            'created_by' => $this->user->id,
            'content' => [
                'blocks' => [
                    [
                        'type' => 'paragraph',
                        'data' => ['text' => 'Test content for version'],
                    ],
                ],
            ],
        ]);
});

test('can mount component with document id', function () {
    Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ])
        ->assertSet('documentId', $this->document->id)
        ->assertSet('isPolling', false)
        ->assertStatus(200);
});

test('can start and stop polling', function () {
    Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ])
        ->call('startPolling')
        ->assertSet('isPolling', true)
        ->call('stopPolling')
        ->assertSet('isPolling', false);
});

test('displays existing versions', function () {
    $component = Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ]);

    $versions = $component->get('versions');

    expect($versions)->toHaveCount(3);
    expect($versions->first()['author'])->toBe($this->user->name);
    expect($versions->first()['content_preview'])->toContain('Test content for version');
});

test('can handle version selection', function () {
    $version = $this->versions->first();

    Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ])
        ->call('handleVersionSelection', $version->id)
        ->assertSet('currentEditingVersion', $version->id)
        ->assertDispatched('document-version-changed', versionId: $version->id);
});

test('can check for new versions', function () {
    $component = Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ]);

    // Create a new version after component is mounted
    $newVersion = DocumentVersion::factory()->create([
        'document_id' => $this->document->id,
        'created_by' => $this->user->id,
        'created_at' => now(),
    ]);

    $newVersions = $component->call('checkForNewVersions')->returnValue();

    expect($newVersions)->toHaveCount(1);
    expect($newVersions[0]['id'])->toBe($newVersion->id);
});

test('generates proper content preview from editor blocks', function () {
    $content = [
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => ['text' => 'This is a test paragraph with some content.'],
            ],
            [
                'type' => 'header',
                'data' => ['text' => 'This is a header'],
            ],
        ],
    ];

    $version = DocumentVersion::factory()->create([
        'document_id' => $this->document->id,
        'created_by' => $this->user->id,
        'content' => $content,
    ]);

    $component = Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ]);

    $versions = $component->get('versions');
    $testVersion = collect($versions)->firstWhere('id', $version->id);

    expect($testVersion['content_preview'])->toContain('This is a test paragraph');
    expect($testVersion['word_count'])->toBeGreaterThan(0);
    expect($testVersion['char_count'])->toBeGreaterThan(0);
});

test('handles empty content blocks gracefully', function () {
    $version = DocumentVersion::factory()->create([
        'document_id' => $this->document->id,
        'created_by' => $this->user->id,
        'content' => ['blocks' => []],
    ]);

    $component = Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ]);

    $versions = $component->get('versions');
    $testVersion = collect($versions)->firstWhere('id', $version->id);

    expect($testVersion['content_preview'])->toBe('Empty document');
    expect($testVersion['word_count'])->toBe(0);
    expect($testVersion['char_count'])->toBe(0);
});

test('limits versions for performance', function () {
    // Create more than 20 versions
    DocumentVersion::factory()
        ->count(25)
        ->create([
            'document_id' => $this->document->id,
            'created_by' => $this->user->id,
        ]);

    $component = Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ]);

    $versions = $component->get('versions');

    // Should be limited to 20 versions plus the 3 from beforeEach
    expect($versions)->toHaveCount(20);
});

test('versions are ordered by creation date descending', function () {
    $component = Livewire::test(DocumentVersionTimeline::class, [
        'documentId' => $this->document->id,
    ]);

    $versions = $component->get('versions');

    // Versions should be ordered newest first
    $timestamps = collect($versions)->pluck('created_at')->map(fn($ts) => strtotime($ts));
    $sortedTimestamps = $timestamps->sort()->reverse()->values();

    expect($timestamps->values()->toArray())->toBe($sortedTimestamps->toArray());
});
