<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class AlertConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    public ?string $shortcut;

    protected function defaults(): array
    {
        return [
            'class' => 'Alert',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'shortcut' => 'CMD+SHIFT+A',
            'config' => [
                'alertTypes' => [
                    'primary',
                    'secondary',
                    'info',
                    'success',
                    'warning',
                    'danger',
                    'light',
                    'dark',
                ],
                'defaultType' => 'primary',
                'messagePlaceholder' => 'Enter something',
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
