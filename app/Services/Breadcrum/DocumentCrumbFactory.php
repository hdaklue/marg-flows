<?php

declare(strict_types=1);

namespace App\Services\Breadcrum;

use App\Contracts\Document\Documentable;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Document;
use Hdaklue\Actioncrumb\Action;
use Hdaklue\Actioncrumb\Step;

final class DocumentCrumbFactory
{
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

    public function view(Document $document)
    {
        $documentable = $document->loadMissing('documentable')->documentable;
        $label = $document->name;
        $this->documentable = $documentable;
        $url = self::resolveUrl($documentable);
        $labelDocumentable = $documentable->getAttribute('title');

        return [
            Step::make($labelDocumentable)->icon('heroicon-o-home')->url($url),
            Step::make($label)
                ->icon('heroicon-o-users')
                ->current()
                ->actions([
                    Action::make('Export Users')->icon('heroicon-o-arrow-down-tray')->url('/'),
                    Action::make('Import Users')->icon('heroicon-o-arrow-up-tray')->url('/'),
                    Action::make('User Settings')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->execute(fn () => dd('archiving')),
                ]),
        ];
    }
}
