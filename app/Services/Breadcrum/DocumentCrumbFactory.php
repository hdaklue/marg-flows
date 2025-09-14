<?php

declare(strict_types=1);

namespace App\Services\Breadcrum;

use App\Contracts\Document\Documentable;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Document;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Hdaklue\Actioncrumb\Action;
use Hdaklue\Actioncrumb\Step;
use Livewire\Component;

final class DocumentCrumbFactory extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;

    public $documentable;

    public static function make()
    {
        return new self;
    }

    private static function resolveUrl(Documentable $documentable)
    {
        return match ($documentable->getMorphClass()) {
            'flow' => FlowResource::getUrl('view', [
                'tanant' => filamentTenant(),
                'record' => $documentable->getKey(),
            ]),
        };
    }

    private static function resolveDocumentsStepUrl(Documentable $documentable)
    {
        return match ($documentable->getMorphClass()) {
            'flow' => FlowResource::getUrl('view', [
                'record' => $documentable->getKey(),
                'activeTab' => 'documents',
            ]),
        };
    }

    public function testAction()
    {
        return FilamentAction::make('test')->action(fn () => dd('works'));
    }

    public function view(Document $document)
    {
        $documentable = $document->loadMissing('documentable')->documentable;
        $this->documentable = $documentable;
        $label = $document->name;
        $this->documentable = $documentable;
        $url = self::resolveUrl($documentable);
        $labelDocumentable = $documentable->getAttribute('title');

        return [
            Step::make($labelDocumentable)->icon(FlowResource::getNavigationIcon())->url($url),
            Step::make('Documents')->url(self::resolveDocumentsStepUrl($documentable))->actions([
                Action::make('Create'),
            ]),
            Step::make($label)
                ->current()
                ->actions([
                    Action::make('Export Users')->icon('heroicon-o-arrow-down-tray')->url('/'),
                    Action::make('Import Users')->icon('heroicon-o-arrow-up-tray')->url('/'),
                    Action::make('Create Document')->icon('heroicon-o-plus')->url('/'),
                ]),
        ];
    }
}
