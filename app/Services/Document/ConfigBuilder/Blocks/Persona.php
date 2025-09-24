<?php

declare(strict_types=1);

namespace App\Services\Document\ConfigBuilder\Blocks;

use App\Services\Document\ConfigBuilder\Blocks\DTO\PersonaConfigData;
use App\Services\Document\ContentBlocks\PersonaBlock;
use App\Services\Document\Contratcs\BlockConfigContract;
use App\Services\Document\Contratcs\DocumentBlockConfigContract;

final class Persona implements DocumentBlockConfigContract
{
    private const string CLASS_NAME = 'PersonaBlock';

    private array $config;

    private array $tunes = ['commentTune'];

    private null|string $shortcut = 'CMD+SHIFT+P';

    public function __construct(
        private bool $inlineToolBar = false,
    ) {
        $this->initializeConfig();
    }

    public function namePlaceholder(string $placeholder): self
    {
        $this->config['namePlaceholder'] = $placeholder;

        return $this;
    }

    public function locationPlaceholder(string $placeholder): self
    {
        $this->config['locationPlaceholder'] = $placeholder;

        return $this;
    }

    public function interestsPlaceholder(string $placeholder): self
    {
        $this->config['interestsPlaceholder'] = $placeholder;

        return $this;
    }

    public function defaultColor(string $color): self
    {
        $this->config['defaultColor'] = $color;

        return $this;
    }

    public function defaultGender(string $gender): self
    {
        $this->config['defaultGender'] = $gender;

        return $this;
    }

    public function defaultAgeRange(string $ageRange): self
    {
        $this->config['defaultAgeRange'] = $ageRange;

        return $this;
    }

    public function defaultChannel(string $channel): self
    {
        $this->config['defaultChannel'] = $channel;

        return $this;
    }

    public function nameMaxLength(int $length): self
    {
        $this->config['validation']['nameMaxLength'] = $length;

        return $this;
    }

    public function locationMaxLength(int $length): self
    {
        $this->config['validation']['locationMaxLength'] = $length;

        return $this;
    }

    public function interestsMaxLength(int $length): self
    {
        $this->config['validation']['interestsMaxLength'] = $length;

        return $this;
    }

    public function nameRequired(bool $required = true): self
    {
        $this->config['validation']['nameRequired'] = $required;

        return $this;
    }

    public function locationRequired(bool $required = true): self
    {
        $this->config['validation']['locationRequired'] = $required;

        return $this;
    }

    public function interestsRequired(bool $required = true): self
    {
        $this->config['validation']['interestsRequired'] = $required;

        return $this;
    }

    public function shortcut(null|string $shortcut): self
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function inlineToolBar(bool $enabled = true): self
    {
        $this->inlineToolBar = $enabled;

        return $this;
    }

    public function withTunes(array $tunes): self
    {
        $this->tunes = $tunes;

        return $this;
    }

    public function addTune(string $tune): self
    {
        if (!in_array($tune, $this->tunes)) {
            $this->tunes[] = $tune;
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->build()->toArray();
    }

    public function toJson($options = 0): string
    {
        return $this->build()->toJson();
    }

    public function toPrettyJson(): string
    {
        return $this->build()->toPrettyJson();
    }

    public function build(): BlockConfigContract
    {
        // Add predefined data from PersonaBlock class
        $this->config['predefinedPersonas'] = $this->getPredefinedPersonas();
        $this->config['predefinedColors'] = PersonaBlock::getPredefinedColors();
        $this->config['predefinedGenders'] = $this->getLocalizedGenders();
        $this->config['predefinedAgeRanges'] = PersonaBlock::getPredefinedAgeRanges();
        $this->config['predefinedChannels'] = $this->getLocalizedChannels();

        return PersonaConfigData::fromArray([
            'config' => $this->config,
            'class' => self::CLASS_NAME,
            'tunes' => $this->tunes,
            'inlineToolBar' => $this->inlineToolBar,
            'shortcut' => $this->shortcut,
        ]);
    }

    /**
     * Initialize configuration with localized defaults.
     */
    private function initializeConfig(): void
    {
        $currentLocale = app()->getLocale();

        if ($currentLocale === 'ar') {
            $this->config = [
                'namePlaceholder' => 'أدخل اسم الشخصية...',
                'locationPlaceholder' => 'أدخل الموقع...',
                'interestsPlaceholder' => 'أدخل الاهتمامات (مفصولة بفواصل)...',
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
            ];
        } else {
            $this->config = [
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
            ];
        }
    }

    /**
     * Get predefined persona names for quick selection.
     */
    private function getPredefinedPersonas(): array
    {
        return [
            'Marketing Manager Sarah',
            'Tech-Savvy Millennial Alex',
            'Budget-Conscious Parent Chris',
            'Fitness Enthusiast Jordan',
            'Small Business Owner Sam',
            'College Student Riley',
            'Senior Executive Morgan',
            'Health-Conscious Professional Casey',
            'Creative Freelancer Taylor',
            'Eco-Conscious Consumer Avery',
        ];
    }

    /**
     * Get localized gender options based on current app locale.
     */
    private function getLocalizedGenders(): array
    {
        $currentLocale = app()->getLocale();

        if ($currentLocale === 'ar') {
            return PersonaBlock::getPredefinedGendersArabic();
        }

        return PersonaBlock::getPredefinedGenders();
    }

    /**
     * Get localized channel options based on current app locale.
     */
    private function getLocalizedChannels(): array
    {
        $currentLocale = app()->getLocale();

        if ($currentLocale === 'ar') {
            return PersonaBlock::getPredefinedChannelsArabic();
        }

        return PersonaBlock::getPredefinedChannels();
    }
}
