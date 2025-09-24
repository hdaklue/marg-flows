<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Schemas;

use App\Enums\FlowStage;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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

                return Flow::query()
                    ->unless($isAdmin, function ($query) {
                        $query->forParticipant(filamentUser());
                    })
                    ->orderBy('stage')
                    ->orderByDesc('updated_at')
                    ->with(['creator']);
            })
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferLoading()
            ->recordUrl(fn(Model $record) => FlowResource::getUrl('view', ['record' =>
                $record->getKey()]))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('title')
                            ->colors([Color::Red[400]])
                            ->formatStateUsing(fn($state) => str($state)->title()->toString())
                            ->label(__('flow.table.columns.title'))
                            ->weight(FontWeight::Bold),
                        TextColumn::make('description')
                            ->color(Color::Red[400])
                            ->formatStateUsing(fn($state) => str($state)->ucfirst()->toString())
                            ->limit(80),
                        TextColumn::make('started_at')->formatStateUsing(fn($state) => 'Started: '
                        . toUserDate($state, filamentUser())),
                    ]),
                    // ImageColumn::make('creator_avatar')
                    //     ->label(__('flow.table.columns.creator_avatar'))
                    //     ->getStateUsing(fn($record) => avatarUrlFromUser($record->creator))
                    //     ->imageSize(30)
                    //     ->circular(),
                    Stack::make([
                        SelectColumn::make('stage')
                            ->label(__('flow.table.columns.stage'))
                            ->grow(false)
                            ->options(options: FlowStage::asFilamentHtmlArray())
                            ->afterStateUpdated(function ($record, $state, $livewire) {
                                $record->update([
                                    'stage' => $state,
                                ]);
                                $livewire->resetTable();
                            })
                            ->allowOptionsHtml()
                            ->selectablePlaceholder(false)
                            ->native(false),
                    ]),
                ])->from('md'),
                // ImageColumn::make('participant_stack')
                //     ->label(__('flow.table.columns.participant_stack'))
                //     ->getStateUsing(fn ($record) => $record->getParticipants()->avatars()->toArray())
                //     ->imageHeight(30)
                //     ->circular()
                //     ->stacked()
                //     ->limit(3)
                //     ->limitedRemainingText(),
            ])
            ->filters([], FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->color('gray')
                    ->form([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->maxLength(255),
                    ]),
                Action::make('view')
                    ->label(__('flow.table.actions.view'))
                    ->color('gray')
                    ->icon('heroicon-s-clipboard-document-list')
                    ->iconButton()
                    ->url(fn($record) => FlowResource::getUrl('pages', [
                        'record' => $record,
                    ])),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
