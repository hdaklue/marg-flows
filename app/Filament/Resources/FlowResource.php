<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\FlowStage;
use App\Filament\Resources\FlowResource\Pages\CreateDocument;
use App\Filament\Resources\FlowResource\Pages\CreateFlow;
use App\Filament\Resources\FlowResource\Pages\FlowDocuments;
use App\Filament\Resources\FlowResource\Pages\ListFlows;
use App\Filament\Resources\FlowResource\Pages\ViewFlow;
use App\Models\Flow;
use App\Services\Flow\TimeProgressService;
use App\Tables\Columns\Progress;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Tables;
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

    protected static ?string $slug = 'f';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        $flowProgressService = app(TimeProgressService::class);

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $isAdmin = filamentTenant()->isAdmin(filamentUser());
                $query->unless($isAdmin, function ($query) {
                    $query->forParticipant(filamentUser());
                })->running()->orderBy('stage')->with(['creator', 'participants']);
            })
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferLoading()
            ->recordUrl(fn (Model $record) => FlowResource::getUrl('view', ['record' => $record->getKey()]))
            ->columns([
                TextColumn::make('title')
                    ->weight(FontWeight::Bold),
                TextColumn::make('stage')
                    ->getStateUsing(fn ($record) => ucfirst(FlowStage::from($record->stage)->getLabel()))
                    ->color(fn ($record) => FlowStage::from($record->stage)->getFilamentColor())
                    ->badge(),
                ImageColumn::make('creator.avatar')
                    // ->getStateUsing(fn ($record) => filament()->getUserAvatarUrl($record->creator))
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),

                // SelectColumn::make('status')
                //     ->options(FlowStatus::class),

                // Progress::make('time_progress')
                //     ->getStateUsing(fn ($record) => $flowProgressService->getProgressDetails($record)),
                // TextColumn::make('start_date')
                //     ->date(),
                // TextColumn::make('due_date')
                //     ->date(),
                // TextColumn::make('days_left')
                //     ->getStateUsing(fn ($record) => $flowProgressService->getDaysRemaining($record)),
                // TextColumn::make('duration')
                //     ->getStateUsing(fn ($record) => $flowProgressService->getTotalDays($record)),

                ImageColumn::make('participant_stack')
                    ->getStateUsing(fn ($record) => $record->getParticipants()->avatars()->toArray())
                    ->imageHeight(40)
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->ring(5)
                    ->limitedRemainingText(),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->options(FlowStage::class),
            ], FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::ExtraSmall),
                Action::make('view')
                    ->label('Documents')
                    ->color('gray')
                    ->size(Size::ExtraSmall)
                    ->icon('heroicon-o-document-text')
                    ->outlined()
                    ->iconButton()
                    ->url(fn ($record) => FlowResource::getUrl('pages', ['record' => $record])),
            ])
            ->toolbarActions([
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

    // public static function getRecordSubNavigation(Page $page): array
    // {
    //     return $page->generateNavigationItems([
    //         FlowPages::class,
    //     ]);
    // }

    public static function getPages(): array
    {
        return [
            'index' => ListFlows::route('/'),
            'create' => CreateFlow::route('/create'),
            'view' => ViewFlow::route('/{record}'),
            'pages' => FlowDocuments::route('{record}/ps'),
            'createDocument' => CreateDocument::route('{flow}/p/c'),
        ];
    }
}
