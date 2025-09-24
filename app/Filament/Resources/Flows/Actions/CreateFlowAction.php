<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Actions;

use App\Actions\Flow\CreateFlow;
use App\DTOs\Flow\CreateFlowDto;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Livewire\Component;

final class CreateFlowAction
{
    public static function make(null|Component $component)
    {
        return Action::make('create')
            ->visible(filamentUser()->can('create', [
                Flow::class,
                filamentTenant(),
            ]))
            ->label(__('flow.actions.create'))
            ->outlined()
            ->form([
                TextInput::make('title')->required()->maxLength(100),
                Textarea::make('description')->maxLength(255),
            ])
            ->action(function (array $data) use ($component) {
                try {
                    $dto = CreateFlowDto::fromArray([
                        'title' => $data['title'],
                        'description' => $data['description'],
                    ]);

                    $createdFlow = CreateFlow::run($dto, filamentTenant(), filamentUser());
                    Notification::make()
                        ->body(__('common.messages.operation_completed'))
                        ->success()
                        ->send();
                    $component->redirect(FlowResource::getUrl('view', [
                        'tenant' => filamentTenant(),
                        'record' => $createdFlow->getKey(),
                    ]), true);
                } catch (Exception $e) {
                    logger()->error($e->getMessage());
                    Notification::make()
                        ->body(__('common.messages.operation_failed'))
                        ->danger()
                        ->send();
                }
            });
    }
}
