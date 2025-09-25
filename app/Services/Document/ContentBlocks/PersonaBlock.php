<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

use BumpCore\EditorPhp\Block\Block;
use Faker\Generator;

final class PersonaBlock extends Block
{
    /**
     * Get predefined color options for persona cards.
     */
    public static function getPredefinedColors(): array
    {
        return [
            'red' => '#ef4444',
            'orange' => '#f97316',
            'amber' => '#f59e0b',
            'yellow' => '#eab308',
            'lime' => '#84cc16',
            'green' => '#22c55e',
            'emerald' => '#10b981',
            'teal' => '#14b8a6',
            'cyan' => '#06b6d4',
            'sky' => '#0ea5e9',
            'blue' => '#3b82f6',
            'indigo' => '#6366f1',
            'violet' => '#8b5cf6',
            'purple' => '#a855f7',
            'fuchsia' => '#d946ef',
            'pink' => '#ec4899',
            'rose' => '#f43f5e',
        ];
    }

    /**
     * Get predefined gender options.
     */
    public static function getPredefinedGenders(): array
    {
        return [
            'both' => 'Both',
            'male' => 'Male',
            'female' => 'Female',
        ];
    }

    /**
     * Get predefined gender options in Arabic.
     */
    public static function getPredefinedGendersArabic(): array
    {
        return [
            'both' => 'كلاهما',
            'male' => 'ذكر',
            'female' => 'أنثى',
        ];
    }

    /**
     * Get predefined age ranges.
     */
    public static function getPredefinedAgeRanges(): array
    {
        return [
            '18-24' => '18-24',
            '25-34' => '25-34',
            '35-44' => '35-44',
            '45-54' => '45-54',
            '55-64' => '55-64',
            '65+' => '65+',
        ];
    }

    /**
     * Get predefined communication channels.
     */
    public static function getPredefinedChannels(): array
    {
        return [
            'email' => 'Email',
            'sms' => 'SMS',
            'social-media' => 'Social Media',
            'phone' => 'Phone',
            'in-person' => 'In-Person',
            'push-notifications' => 'Push Notifications',
            'direct-mail' => 'Direct Mail',
            'messaging-apps' => 'Messaging Apps',
        ];
    }

    /**
     * Get predefined communication channels in Arabic.
     */
    public static function getPredefinedChannelsArabic(): array
    {
        return [
            'email' => 'البريد الإلكتروني',
            'sms' => 'الرسائل النصية',
            'social-media' => 'وسائل التواصل الاجتماعي',
            'phone' => 'الهاتف',
            'in-person' => 'شخصياً',
            'push-notifications' => 'الإشعارات',
            'direct-mail' => 'البريد المباشر',
            'messaging-apps' => 'تطبيقات المراسلة',
        ];
    }

    /**
     * Generate fake data for testing.
     */
    public static function fake(Generator $faker): array
    {
        $colors = array_keys(self::getPredefinedColors());
        $genders = array_keys(self::getPredefinedGenders());
        $ageRanges = array_keys(self::getPredefinedAgeRanges());
        $channels = array_keys(self::getPredefinedChannels());

        $interests = [
            'technology',
            'fitness',
            'travel',
            'cooking',
            'music',
            'reading',
            'sports',
            'gaming',
            'fashion',
            'photography',
            'art',
            'movies',
            'gardening',
            'yoga',
            'investing',
            'education',
            'health',
            'sustainability',
        ];

        return [
            'name' => $faker->name(),
            'age_range' => $faker->randomElement($ageRanges),
            'gender' => $faker->randomElement($genders),
            'location' => $faker->city() . ', ' . $faker->country(),
            'interests' => implode(', ', $faker->randomElements($interests, $faker->numberBetween(
                3,
                6,
            ))),
            'preferred_channel' => $faker->randomElement($channels),
            'color' => $faker->randomElement($colors),
        ];
    }

    /**
     * Validation rules for the persona block data.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'age_range' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:255'],
            'interests' => ['required', 'string', 'max:500'],
            'preferred_channel' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * Allowed HTML tags for content purification.
     */
    public function allows(): array
    {
        return [
            'name' => 'b,i,em,strong',
            'interests' => 'b,i,em,strong',
            'location' => 'b,i,em,strong',
        ];
    }

    /**
     * Check if the block has valid persona data.
     */
    public function hasPersona(): bool
    {
        $name = $this->get('name');
        $ageRange = $this->get('age_range');
        $gender = $this->get('gender');

        return
            ! empty($name)
            && is_string($name)
            && trim($name) !== ''
            && ! empty($ageRange)
            && is_string($ageRange)
            && ! empty($gender)
            && is_string($gender);
    }

    /**
     * Get the persona name.
     */
    public function getName(): string
    {
        return (string) $this->get('name', '');
    }

    /**
     * Get the persona age range.
     */
    public function getAgeRange(): string
    {
        return (string) $this->get('age_range', '');
    }

    /**
     * Get the persona gender.
     */
    public function getGender(): string
    {
        return (string) $this->get('gender', '');
    }

    /**
     * Get the persona location.
     */
    public function getLocation(): string
    {
        return (string) $this->get('location', '');
    }

    /**
     * Get the persona interests.
     */
    public function getInterests(): string
    {
        return (string) $this->get('interests', '');
    }

    /**
     * Get the preferred communication channel.
     */
    public function getPreferredChannel(): string
    {
        return (string) $this->get('preferred_channel', '');
    }

    /**
     * Get the persona color.
     */
    public function getColor(): string
    {
        return (string) $this->get('color', 'blue');
    }

    /**
     * Get the color hex value.
     */
    public function getColorHex(): string
    {
        $colors = self::getPredefinedColors();

        return $colors[$this->getColor()] ?? $colors['blue'];
    }

    /**
     * Check if the block is empty (no meaningful content).
     */
    public function isEmpty(): bool
    {
        return ! $this->hasPersona();
    }

    /**
     * Get the display text for the persona.
     */
    public function getDisplayText(): string
    {
        if (! $this->hasPersona()) {
            return '';
        }

        return sprintf(
            '%s (%s, %s) - %s',
            $this->getName(),
            $this->getGender(),
            $this->getAgeRange(),
            $this->getLocation(),
        );
    }

    /**
     * Get the persona icon HTML.
     */
    public function getPersonaIcon(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>';
    }

    /**
     * Get the CSS class for the persona.
     */
    public function getPersonaClass(): string
    {
        return 'persona-block--' . $this->getColor();
    }

    /**
     * Get interests as an array.
     */
    public function getInterestsArray(): array
    {
        $interests = $this->getInterests();
        if (empty($interests)) {
            return [];
        }

        return array_map('trim', explode(',', $interests));
    }

    /**
     * Get the channel display name.
     */
    public function getChannelDisplayName(): string
    {
        $channels = self::getPredefinedChannels();

        return $channels[$this->getPreferredChannel()] ?? $this->getPreferredChannel();
    }

    /**
     * Get the gender display name.
     */
    public function getGenderDisplayName(): string
    {
        $genders = self::getPredefinedGenders();

        return $genders[$this->getGender()] ?? $this->getGender();
    }

    /**
     * Get the gender display name in Arabic.
     */
    public function getGenderDisplayNameArabic(): string
    {
        $genders = self::getPredefinedGendersArabic();

        return $genders[$this->getGender()] ?? $this->getGender();
    }

    /**
     * Get the channel display name in Arabic.
     */
    public function getChannelDisplayNameArabic(): string
    {
        $channels = self::getPredefinedChannelsArabic();

        return $channels[$this->getPreferredChannel()] ?? $this->getPreferredChannel();
    }

    /**
     * Render the persona block to HTML.
     */
    public function render(): string
    {
        if (! $this->hasPersona()) {
            return '';
        }

        $name = htmlspecialchars($this->getName(), ENT_QUOTES, 'UTF-8');
        $ageRange = htmlspecialchars($this->getAgeRange(), ENT_QUOTES, 'UTF-8');
        $gender = htmlspecialchars($this->getGenderDisplayName(), ENT_QUOTES, 'UTF-8');
        $location = htmlspecialchars($this->getLocation(), ENT_QUOTES, 'UTF-8');
        $channel = htmlspecialchars($this->getChannelDisplayName(), ENT_QUOTES, 'UTF-8');
        $colorHex = $this->getColorHex();
        $personaClass = $this->getPersonaClass();
        $personaIcon = $this->getPersonaIcon();
        $interests = $this->getInterestsArray();

        $interestsHtml = '';
        foreach ($interests as $interest) {
            $interest = htmlspecialchars(trim($interest), ENT_QUOTES, 'UTF-8');
            $interestsHtml .= "<span class=\"persona-block__interest-tag\">{$interest}</span>";
        }

        return <<<HTML
        <div class="persona-block__display {$personaClass}" data-block-type="persona" style="border-left: 4px solid {$colorHex};">
            <div class="persona-block__display-content">
                <div class="persona-block__display-header">
                    <span class="persona-block__display-icon" style="color: {$colorHex};">{$personaIcon}</span>
                    <div class="persona-block__display-info">
                        <h3 class="persona-block__display-name">{$name}</h3>
                        <div class="persona-block__display-demographics">
                            <span class="persona-block__display-age">{$ageRange}</span>
                            <span class="persona-block__display-separator">•</span>
                            <span class="persona-block__display-gender">{$gender}</span>
                            <span class="persona-block__display-separator">•</span>
                            <span class="persona-block__display-location">{$location}</span>
                        </div>
                    </div>
                </div>
                <div class="persona-block__display-details">
                    <div class="persona-block__display-interests">
                        <strong>Interests:</strong>
                        <div class="persona-block__interests-list">{$interestsHtml}</div>
                    </div>
                    <div class="persona-block__display-channel">
                        <strong>Preferred Channel:</strong> {$channel}
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Render the persona block to HTML with RTL support.
     */
    public function renderRtl(): string
    {
        if (! $this->hasPersona()) {
            return '';
        }

        $name = htmlspecialchars($this->getName(), ENT_QUOTES, 'UTF-8');
        $ageRange = htmlspecialchars($this->getAgeRange(), ENT_QUOTES, 'UTF-8');
        $gender = htmlspecialchars($this->getGenderDisplayNameArabic(), ENT_QUOTES, 'UTF-8');
        $location = htmlspecialchars($this->getLocation(), ENT_QUOTES, 'UTF-8');
        $channel = htmlspecialchars($this->getChannelDisplayNameArabic(), ENT_QUOTES, 'UTF-8');
        $colorHex = $this->getColorHex();
        $personaClass = $this->getPersonaClass();
        $personaIcon = $this->getPersonaIcon();
        $interests = $this->getInterestsArray();

        $interestsHtml = '';
        foreach ($interests as $interest) {
            $interest = htmlspecialchars(trim($interest), ENT_QUOTES, 'UTF-8');
            $interestsHtml .= "<span class=\"persona-block__interest-tag\">{$interest}</span>";
        }

        return <<<HTML
        <div class="persona-block__display {$personaClass}" data-block-type="persona" dir="rtl" style="border-right: 4px solid {$colorHex};">
            <div class="persona-block__display-content">
                <div class="persona-block__display-header">
                    <span class="persona-block__display-icon" style="color: {$colorHex};">{$personaIcon}</span>
                    <div class="persona-block__display-info">
                        <h3 class="persona-block__display-name">{$name}</h3>
                        <div class="persona-block__display-demographics">
                            <span class="persona-block__display-age">{$ageRange}</span>
                            <span class="persona-block__display-separator">•</span>
                            <span class="persona-block__display-gender">{$gender}</span>
                            <span class="persona-block__display-separator">•</span>
                            <span class="persona-block__display-location">{$location}</span>
                        </div>
                    </div>
                </div>
                <div class="persona-block__display-details">
                    <div class="persona-block__display-interests">
                        <strong>الاهتمامات:</strong>
                        <div class="persona-block__interests-list">{$interestsHtml}</div>
                    </div>
                    <div class="persona-block__display-channel">
                        <strong>القناة المفضلة:</strong> {$channel}
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Get summary data for analytics or reporting.
     */
    public function getSummary(): array
    {
        return [
            'type' => 'persona',
            'name' => $this->getName(),
            'age_range' => $this->getAgeRange(),
            'gender' => $this->getGender(),
            'location' => $this->getLocation(),
            'interests' => $this->getInterestsArray(),
            'preferred_channel' => $this->getPreferredChannel(),
            'color' => $this->getColor(),
            'color_hex' => $this->getColorHex(),
            'display_text' => $this->getDisplayText(),
            'is_empty' => $this->isEmpty(),
        ];
    }
}
