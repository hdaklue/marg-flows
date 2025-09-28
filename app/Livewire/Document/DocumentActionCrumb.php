<?php

declare(strict_types=1);

namespace App\Livewire\Document;

use App\Livewire\Steps\DocumentStep;
use App\Models\Document;
use App\Models\Flow;
use Hdaklue\Actioncrumb\Components\WireCrumb;
use Hdaklue\Actioncrumb\Components\WireStep;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Document-specific breadcrumb component with actions.
 */
final class DocumentActionCrumb extends WireCrumb
{
    #[Locked]
    public string $documentId;

    public function mount($record = null, $parent = null)
    {
        parent::mount($record, $parent);
        $this->documentId = $record->id;
    }

    #[Computed]
    public function document(): Document
    {
        return Document::with('documentable')->findOrFail($this->documentId);
    }

    #[Computed]
    public function flow(): Flow
    {
        return $this->document->documentable;
    }

    public function render()
    {
        return view('livewire.document.document-action-crumb', [
            'renderedCrumbSteps' => $this->renderCrumbSteps(),
        ]);
    }

    protected function crumbSteps(): array
    {
        if (! $this->document) {
            return [];
        }

        return [
            WireStep::make(DocumentStep::class, [
                'record' => $this->document,
                'stepType' => 'flow',
            ])->stepId('flow'),
            WireStep::make(DocumentStep::class, [
                'record' => $this->document,
                'stepType' => 'document',
            ])->stepId('document'),
            WireStep::make(DocumentStep::class, [
                'record' => $this->document,
                'stepType' => 'current',
            ])->stepId('current'),
        ];
    }
}
