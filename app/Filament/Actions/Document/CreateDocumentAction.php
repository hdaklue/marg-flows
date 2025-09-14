<?php

declare(strict_types=1);

namespace App\Filament\Actions\Document;

use App\Contracts\Document\Documentable;
use App\Filament\Resources\Documents\DocumentResource;
use App\Services\Document\Actions\CreateDocument;
use App\Services\Document\Facades\DocumentTemplate;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

final class CreateDocumentAction
{
    public static function make(Documentable $documentable, $shouldRedirect = true)
    {
        return Action::make('add_document')
            ->label('Add Document')
            ->form([
                TextInput::make('name')->required()->maxLength(100),
                Select::make('template')
                    ->options(DocumentTemplate::templatesAsSelectArray())
                    ->required()
                    ->placeholder('Select a template'),
            ])
            ->visible(fn () => filamentUser()->can('manage', $documentable))
            ->action(function (array $data) use ($documentable) {
                try {
                    $driver = (string) $data['template'];

                    if (empty($driver)) {
                        throw new Exception('Template type is required');
                    }

                    $template = DocumentTemplate::$driver();
                    $createdDocument = CreateDocument::run(
                        $data['name'],
                        filamentUser(),
                        $documentable,
                        $template,
                    );
                    // if ($shouldRedirect) {
                    //     $livewire->redirect(DocumentResource::getUrl('view', [
                    //         'record' => $createdDocument->getKey(),
                    //     ]), true);
                    // }

                    Notification::make()
                        ->body(__('common.messages.operation_completed'))
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    logger()->error($e->getMessage());
                    Notification::make()
                        ->body(__('common.messages.operation_failed'))
                        ->danger()
                        ->send();
                }
            });
    }
}
