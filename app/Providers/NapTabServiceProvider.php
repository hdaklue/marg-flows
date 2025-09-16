<?php

declare(strict_types=1);

namespace App\Providers;

use Hdaklue\NapTab\Enums\BadgeSize;
use Hdaklue\NapTab\Enums\ContentAnimation;
use Hdaklue\NapTab\Enums\Shadow;
use Hdaklue\NapTab\Enums\TabBorderRadius;
use Hdaklue\NapTab\Enums\TabBorderWidth;
use Hdaklue\NapTab\Enums\TabColor;
use Hdaklue\NapTab\Enums\TabSpacing;
use Hdaklue\NapTab\Enums\TabStyle;
use Hdaklue\NapTab\Enums\TabTransition;
use Hdaklue\NapTab\Enums\TabTransitionTiming;
use Hdaklue\NapTab\Services\NapTabConfig;
use Illuminate\Support\ServiceProvider;

final class NapTabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('naptab.config', function () {
            return NapTabConfig::create()
                // Basic styling - choose your preferred style preset
                ->style(TabStyle::Minimal)
                ->color(TabColor::Emerald, TabColor::Zinc)
                ->radius(TabBorderRadius::Medium)
                ->spacing(TabSpacing::Small)
                // Shadows and borders
                ->shadow(Shadow::None, 'shadow-blue-500/20 dark:shadow-blue-400/30')
                ->border(TabBorderWidth::Thin, true) // width, double border
                // Transitions and animations
                ->transition(TabTransition::Duration300, TabTransitionTiming::EaseInOut)
                ->contentAnimation(ContentAnimation::Scale)
                // Badge configuration
                ->badgeSize(BadgeSize::Medium)
                ->badgeRadius(TabBorderRadius::Full)
                // Mobile navigation - set to true for modal, false for enhanced scrolling
                ->navModalOnMobile(true);

            // Note: URL routing and layout direction are now controlled per component
            // - Override isRoutable() in your tab components to enable/disable routing per component
            // - Override direction() in your tab components to set horizontal/vertical layout (desktop only)
            // Available enum values:
            // TabStyle: Minimal, Modern, Sharp
            // TabColor: Blue, Red, Green, Yellow, Purple, Pink, Gray, Slate, Stone, Zinc, Neutral, Orange, Amber, Lime, Emerald, Teal, Cyan, Sky, Indigo, Violet, Fuchsia, Rose
            // TabBorderRadius: None, Small, Medium, Large, Full
            // Shadow: None, Small, Medium, Large, ExtraLarge
            // TabBorderWidth: None, Thin, Medium, Thick
            // TabTransition: Duration75, Duration100, Duration150, Duration200, Duration300, Duration500, Duration700, Duration1000
            // TabTransitionTiming: Linear, EaseIn, EaseOut, EaseInOut
            // TabSpacing: Small, Normal, Large
            // ContentAnimation: None, Fade, Scale, Slide
            // BadgeSize: Small, Medium, Large
        });
    }

    public function boot(): void
    {
        //
    }
}
