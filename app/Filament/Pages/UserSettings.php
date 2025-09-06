<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\User\UpdateBasicInfo;
use App\DTOs\User\UserDto;
use App\Filament\Tables\UserTenant;
use App\Services\Avatar\AvatarService;
use App\Services\Timezone;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

final class UserSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.profile';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable
    {
        return __('auth.profile.title');
    }

    public function table(Table $table): Table
    {
        return UserTenant::configure($table);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('auth.profile.sections.general'))
                ->schema([
                    Grid::make(12)
                        ->schema([
                            Image::make(filamentUser()->getAvatarUrl(), filamentUser()->name)
                                ->imageSize(50),
                            Grid::make(1)
                                ->schema([
                                    Text::make(fn () => filamentUser()->name)
                                        ->weight(FontWeight::Bold),
                                    Text::make(filamentUser()->email),
                                    Text::make(filamentUser()->displayTimeZone()),
                                ])
                                ->columnSpan(4),
                        ]),
                ])->afterHeader([
                    Action::make('edit')
                        ->label(__('auth.profile.actions.edit'))
                        ->color('primary')
                        ->outlined()
                        ->fillForm(fn () => [
                            'email' => filamentUser()->getAttribute('email'),
                            'avatar' => AvatarService::getAvatarPath(filamentUser()) ?: null,
                            'timezone' => filamentUser()->getTimeZone(),
                        ])
                        ->form([
                            TextInput::make('email')
                                ->label(__('auth.profile.fields.email'))
                                ->email(),
                            FileUpload::make('avatar')
                                ->label(__('auth.profile.fields.avatar'))
                                ->image()
                                ->imageEditorViewportWidth(600)
                                ->imageEditorViewportHeight(400)
                                ->avatar()
                                ->imageEditor()
                                ->circleCropper()
                                ->disk('public'),
                            Select::make('timezone')
                                ->label(__('auth.profile.fields.timezone'))
                                ->searchable()
                                ->options(Timezone::getTimezonesAsSelectList()),
                        ])
                        ->action(fn (array $data) => $this->updateUserBasicData($data)),
                ]),

            Section::make(__('auth.profile.sections.teams'))
                ->schema([
                    EmbeddedTable::make(),
                ]),

        ]);
    }

    private function updateUserBasicData(array $data)
    {
        $dto = UserDto::fromArray([
            'id' => filamentUser()->getKey(),
            'name' => filamentUser()->getAttribute('name'),
            'avatar' => $data['avatar'],
            'email' => $data['email'],
            'timezone' => $data['timezone'],
        ]);
        try {
            UpdateBasicInfo::run($dto, filamentUser());
            Notification::make()
                ->body('Setting updated successfully')
                ->success()
                ->send();
        } catch (Exception $e) {
            Logger()->error(($e->getMessage()));
            Notification::make()
                ->body('Something went wrong')
                ->danger()
                ->send();
        }

    }
}
