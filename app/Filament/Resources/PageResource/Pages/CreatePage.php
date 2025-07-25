<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

final class CreatePage extends Page
{
    protected static string $resource = PageResource::class;

    public function mount(string $pageable): void
    {
        dd('implement creation process');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                PlaceholderInput::make('title')
                    ->placeholder('Title'),
                EditorJs::make('blocks'),
            ]);
    }
}
