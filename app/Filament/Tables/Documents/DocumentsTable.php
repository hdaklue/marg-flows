<?php

namespace App\Filament\Tables\Documents;

use App\Facades\DocumentManager;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Flow;
use App\Services\Document\DocumentService;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DocumentsTable
{
    public static function configure(Table $table, Flow $flow): Table
    {
        return $table
            ->records(fn(): array => static::getDocuments($flow))
            ->recordUrl(fn($record) => DocumentResource::getUrl('view', [
                'record' => $record['id'],
            ]))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->getStateUsing(fn($record) => toUserDateTime(
                        $record['updated_at'],
                        filamentUser(),
                    )),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    private static function getDocuments($flow): array
    {
        return DocumentManager::getDocumentsForUser($flow, filamentUser())
            ->keyBy(fn($item) => $item->getKey())
            ->toArray();
    }
}
