<?php

declare(strict_types=1);

namespace App\Filament\Tables\Documents;

use App\Contracts\Document\Documentable;
use App\Facades\DocumentManager;
use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DocumentsTable
{
    public static function configure(Table $table, Documentable $documentable): Table
    {
        return $table
            ->records(function ($search) use ($documentable) {
                return self::getDocuments($documentable, $search);
            })
            ->recordUrl(fn ($record) => DocumentResource::getUrl('view', [
                'record' => $record['id'],
            ]))
            ->columns([
                TextColumn::make('name')->grow(false),
                TextColumn::make('description')->grow(),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->getStateUsing(fn ($record) => toUserDateTime(
                        $record['updated_at'],
                        filamentUser(),
                    )),
            ])
            ->searchable(true)
            ->filters([
                //
            ])
            ->headerActions([
                CreateDocumentAction::make($documentable)->outlined(),
            ])
            ->recordActions([
                Action::make('archive')
                    ->label('Archive')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    private static function getDocuments($documentable, $search): array
    {
        return DocumentManager::getDocumentsForUser($documentable, filamentUser())
            ->when($search, function ($collection, $term) {
                return $collection->filter(
                    fn ($item): bool => stripos($item->name, $term) !== false,
                );
            })
            ->keyBy(fn ($item) => $item->getKey())
            ->toArray();
    }
}
