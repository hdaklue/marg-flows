<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks\DTO;

use App\Services\Document\Contratcs\BlockConfigContract;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class ObjectiveConfigData extends SimpleDTO implements BlockConfigContract
{
    public string $class;

    public array $tunes;

    public bool $inlineToolBar;

    public array $config;

    public null|string $shortcut;

    protected function defaults(): array
    {
        return [
            'class' => 'ObjectiveBlock',
            'tunes' => [],
            'inlineToolBar' => false,
            'shortcut' => 'CMD+SHIFT+O',
            'config' => [
                'namePlaceholder' => 'Enter objective name...',
                'operators' => [
                    'increase' => 'Increase',
                    'decrease' => 'Decrease',
                    'equal' => 'Equal',
                ],
                'defaultOperator' => 'increase',
                'percentageMin' => 0,
                'percentageMax' => 100,
                'percentageStep' => 0.1,
                'validation' => [
                    'nameRequired' => true,
                    'nameMaxLength' => 255,
                    'percentageRequired' => true,
                ],
            ],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
