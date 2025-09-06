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
use Hdaklue\Porter\RoleFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowsTable
{
    public static function configure(Table $table): Table
    {

        return $table
            ->query(function () {
                $isAdmin = filamentUser()->hasAssignmentOn(filamentTenant(), RoleFactory::admin());

                return Flow::query()->unless($isAdmin, function ($query) {
                    $query->forParticipant(filamentUser());
                })->orderBy('stage')->with(['creator']);
            })
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferLoading()
            ->recordUrl(fn (Model $record) => FlowResource::getUrl('view', ['record' => $record->getKey()]))
            ->columns([
                TextColumn::make('title')
                    ->label(__('flow.table.columns.title'))
                    ->weight(FontWeight::Bold),

                SelectColumn::make('stage')
                    ->label(__('flow.table.columns.stage'))
                    ->grow(false)
                    ->options(options: FlowStage::asFilamentHtmlArray())
                    ->afterStateUpdated(function ($record, $state, $livewire) {
                        $record->update(['stage' => $state]);
                        $livewire->resetTable();
                    })
                    ->allowOptionsHtml()
                    ->selectablePlaceholder(false)
                    ->native(false),
                TextColumn::make('started_at')
                    ->formatStateUsing(fn ($state) => toUserDate($state, filamentUser())),
                ImageColumn::make('creator_avatar')
                    ->label(__('flow.table.columns.creator_avatar'))
                    ->getStateUsing(fn ($record) => avatarUrlFromUser($record->creator))
                    ->imageSize(30)
                    ->circular(),
                // ImageColumn::make('participant_stack')
                //     ->label(__('flow.table.columns.participant_stack'))
                //     ->getStateUsing(fn ($record) => $record->getParticipants()->avatars()->toArray())
                //     ->imageHeight(30)
                //     ->circular()
                //     ->stacked()
                //     ->limit(3)
                //     ->limitedRemainingText(),
            ])

            ->filters([

            ], FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Small),
                Action::make('view')
                    ->label(__('flow.table.actions.view'))
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
