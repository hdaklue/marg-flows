<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\FlowStatus;
use App\Filament\Pages\ViewFlow;
use App\Filament\Resources\FlowResource\Pages;
use App\Filament\Resources\FlowResource\Pages\FlowPages;
use App\Models\Flow;
use App\Services\Flow\TimeProgressService;
use App\Tables\Columns\Progress;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class FlowResource extends Resource
{
    protected static ?string $model = Flow::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        $flowProgressService = app(TimeProgressService::class);

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $isAdmin = filamentTenant()->isAdmin(filamentUser());
                $query->unless($isAdmin, function ($query) {
                    $query->forParticipant(filamentUser());
                })->running()->with(['creator', 'participants'])->ordered();
            })
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferLoading()
            ->reorderable('order_column')
            ->recordUrl(fn (Model $record) => ViewFlow::getUrl(['record' => $record->getKey()]))
            ->columns([
                TextColumn::make('title')
                    ->weight(FontWeight::Bold),
                TextColumn::make('status')
                    ->getStateUsing(fn ($record) => ucfirst(FlowStatus::from($record->status)->getLabel()))
                    ->color(fn ($record) => FlowStatus::from($record->status)->getFilamentColor())
                    ->badge(),
                ImageColumn::make('creator.avatar')
                    // ->getStateUsing(fn ($record) => filament()->getUserAvatarUrl($record->creator))
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),

                // SelectColumn::make('status')
                //     ->options(FlowStatus::class),

                Progress::make('time_progress')
                    ->getStateUsing(fn ($record) => $flowProgressService->getProgressDetails($record)),
                TextColumn::make('start_date')
                    ->date(),
                TextColumn::make('due_date')
                    ->date(),
                TextColumn::make('days_left')
                    ->getStateUsing(fn ($record) => $flowProgressService->getDaysRemaining($record)),
                TextColumn::make('duration')
                    ->getStateUsing(fn ($record) => $flowProgressService->getTotalDays($record)),

                ImageColumn::make('prticipants')
                    ->getStateUsing(fn ($record) => $record->participants->pluck('model')->pluck('avatar'))
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->options(FlowStatus::class),
            ], FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('view')
                    ->label('Knowledge')
                    ->color('gray')
                    ->outlined()
                    ->url(fn ($record) => FlowResource::getUrl('pages', ['record' => $record])),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            FlowPages::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlows::route('/'),
            'create' => Pages\CreateFlow::route('/create'),
            'pages' => FlowPages::route('/{record}/pages'),
            // 'edit' => Pages\EditFlow::route('/{record}/edit'),
        ];
    }
}
