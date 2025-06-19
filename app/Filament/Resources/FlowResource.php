<?php

namespace App\Filament\Resources;

use App\Enums\FlowStatus;
use App\Filament\Resources\FlowResource\Pages;
use App\Models\Flow;
use App\Services\Flow\FlowProgressService;
use App\Tables\Columns\Progress;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FlowResource extends Resource
{
    protected static ?string $model = Flow::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $flowProgressService = app(FlowProgressService::class);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->forParticipant(filament()->auth()->user())->running()->with(['creator', 'participants'])->orderBy('status')->orderBy('due_date'))
            ->columns([

                TextColumn::make('status')
                    ->getStateUsing(fn ($record) => FlowStatus::from($record->status)->getLabel())
                    ->color(fn ($record) => FlowStatus::from($record->status)->getColor())
                    ->badge(),
                // SelectColumn::make('status')
                //     ->options(FlowStatus::class),
                TextColumn::make('title'),
                Progress::make('time_progress')
                    ->getStateUsing(fn ($record) => $flowProgressService->getProgressDetails($record)),
                TextColumn::make('start_date')
                    ->since(),
                TextColumn::make('due_date')
                    ->since(),
                TextColumn::make('days_left')

                    ->getStateUsing(fn ($record) => $flowProgressService->getDaysRemainingPositive($record)),
                TextColumn::make('duration')
                    ->getStateUsing(fn ($record) => $flowProgressService->getTotalDays($record)),
                ImageColumn::make('creator')
                    ->getStateUsing(fn ($record) => filament()->getUserAvatarUrl($record->creator))
                    ->size(30)
                    ->circular(),
                TextColumn::make('participants.name'),

            ])

            ->filters([
                SelectFilter::make('status')
                    ->options(FlowStatus::class),
                QueryBuilder::make()
                    ->constraints([
                        DateConstraint::make('due_date'),
                    ]),
            ], FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlows::route('/'),
            // 'create' => Pages\CreateFlow::route('/create'),
            // 'edit' => Pages\EditFlow::route('/{record}/edit'),
        ];
    }
}
