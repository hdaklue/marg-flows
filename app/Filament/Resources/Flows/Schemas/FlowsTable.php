<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Schemas;

use App\Enums\FlowStage;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class FlowsTable
{
    public static function configure(Table $table): Table
    {

        return $table
            ->query(function () {
                $isAdmin = filamentTenant()->isAdmin(filamentUser());

                return Flow::query()->unless($isAdmin, function ($query) {
                    $query->forParticipant(filamentUser());
                })->orderBy('stage')->with(['creator', 'participants']);
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
                SelectColumn::make('stage')
                    ->grow(false)
                    ->options(options: FlowStage::asFilamentHtmlArray())
                    ->afterStateUpdated(function ($record, $state, $livewire) {
                        $record->update(['stage' => $state]);
                        $livewire->resetTable();
                    })
                    ->allowOptionsHtml()
                    ->selectablePlaceholder(false)
                    ->native(false),
                ImageColumn::make('creator_avatar')
                    ->getStateUsing(fn ($record) => avatarUrlFromUser($record->creator))
                    ->imageSize(30)
                    ->circular(),
                ImageColumn::make('participant_stack')
                    ->label('Members')
                    ->getStateUsing(fn ($record) => $record->getParticipants()->avatars()->toArray())
                    ->imageHeight(30)
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),
            ])

            ->filters([

            ], FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Small),
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
}
