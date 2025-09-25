<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class PersonaConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    public ?string $shortcut;

    protected function defaults(): array
    {
        return [
            'class' => 'PersonaBlock',
            'tunes' => ['commentTune'],
            'inlineToolBar' => false,
            'shortcut' => 'CMD+SHIFT+P',
            'config' => [
                'namePlaceholder' => 'Enter persona name...',
                'locationPlaceholder' => 'Enter location...',
                'interestsPlaceholder' => 'Enter interests (separated by commas)...',
                'defaultColor' => 'blue',
                'defaultGender' => 'both',
                'defaultAgeRange' => '25-34',
                'defaultChannel' => 'email',
                'predefinedPersonas' => [],
                'predefinedColors' => [],
                'predefinedGenders' => [],
                'predefinedAgeRanges' => [],
                'predefinedChannels' => [],
                'validation' => [
                    'nameRequired' => true,
                    'nameMaxLength' => 255,
                    'ageRangeRequired' => true,
                    'genderRequired' => true,
                    'locationRequired' => true,
                    'locationMaxLength' => 255,
                    'interestsRequired' => true,
                    'interestsMaxLength' => 500,
                    'channelRequired' => true,
                    'colorRequired' => true,
                ],
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
