<?php

declare(strict_types=1);

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

final class DocumentResource extends Resource
{
    protected static null|string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationSort(): int
    {
        return 3;
    }

    public static function getModelLabel(): string
    {
        return __('app.documents');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.documents');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.documents');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('app.name')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            // 'index' => ListDocuments::route('/'),
            'view' => ViewDocument::route('/{record}'),
            // 'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
