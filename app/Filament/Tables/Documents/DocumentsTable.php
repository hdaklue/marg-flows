<?php

declare(strict_types=1);

namespace App\Filament\Tables\Documents;

use App\Contracts\Document\Documentable;
use App\Facades\DocumentManager;
use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Resources\Documents\DocumentResource;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Livewire\Component;
use Throwable;

final class DocumentsTable
{
    public static function configure(Table $table, Documentable $documentable): Table
    {
        return $table
            ->records(function ($search, $filters) use ($documentable) {

                return self::getDocuments($documentable, $search, $filters);
            })
            ->recordUrl(fn ($record) => DocumentResource::getUrl('view', [
                'record' => $record['id'],
            ]))
            ->columns([
                Split::make([
                    TextColumn::make('name')->grow(false),
                    TextColumn::make('description')->grow(),
                ]),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->getStateUsing(fn ($record) => toUserDateTime(
                        $record['updated_at'],
                        filamentUser(),
                    )),
            ])
            ->searchable(true)
            ->filters([
                Filter::make('show_archived')
                    ->toggle()
                    ->default(false),
            ])
            ->headerActions([
                CreateDocumentAction::make($documentable)->outlined(),
            ])
            ->recordActions([
                Action::make('archive')
                    ->label('Archive')
                    ->color('danger')
                    ->icon(Heroicon::ArchiveBoxArrowDown)
                    ->visible(
                        fn (array $record) => (
                            filamentUser()->can('manage', $documentable)
                            && empty($record['archived_at'])
                        ),
                    )
                    ->action(function (array $record, Component $livewire) {
                        $document = DocumentManager::getDocument($record['id']);

                        try {
                            DocumentManager::archive($document);
                            $livewire->resetTable();
                            Notification::make()
                                ->body(__('common.messages.operation_completed'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->body(__('common.messages.operation_failed'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('un_archive')
                    ->label('Restore')
                    ->color('primary')
                    ->icon(Heroicon::ArrowPath)
                    ->visible(
                        fn (array $record) => (
                            filamentUser()->can('manage', $documentable)
                            && ! empty($record['archived_at'])
                        ),
                    )
                    ->action(function (array $record, Component $livewire) {
                        $document = DocumentManager::getDocument($record['id']);

                        try {
                            DocumentManager::restore($document);
                            $livewire->resetTable();

                            Notification::make()
                                ->body(__('common.messages.operation_completed'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->body(__('common.messages.operation_failed'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    /**
     * @throws Throwable
     */
    private static function getDocuments($documentable, $search, array $filters): array
    {
        $canManage = filamentUser()->can('manage', $documentable);

        return DocumentManager::getDocumentsForUser($documentable, filamentUser())
            ->when(! $canManage, function ($collection) {
                return $collection->filter(fn ($item) => ! $item->isArchived());
            })
            ->when($search, function ($collection, $term) {
                return $collection->filter(
                    fn ($item): bool => stripos($item->name, $term) !== false,
                );
            })
            ->when(! $filters['show_archived']['isActive'], fn ($collection) => $collection->filter(
                fn ($item) => ! $item->isArchived(),
            ))
            ->keyBy(fn ($item) => $item->getKey())
            ->toArray();
    }
}
