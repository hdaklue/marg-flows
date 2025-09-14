<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class Timezone
{
    public static function getTimezones(): array
    {
        return Cache::rememberForever('app:time-zones', function () {
            // return collect(countries(hydrate: true))->map(fn ($item) => $item->getTimezones())
            //     ->flatten()->mapWithKeys(fn ($item) => [$item => $item])
            //     ->sort()->toArray();
            return collect(countries(hydrate: true))
                ->flatten()
                ->mapWithKeys(fn ($item) => [
                    $item->getName() => $item->getTimezones(),
                ])
                ->map(fn ($value, $key) => $value)
                ->sortKeys()
                ->toArray();
        });
    }

    public static function getTimezonesAsSelectList(): array
    {
        return collect(self::getTimezones())
            ->flatten(1)
            ->mapWithKeys(function ($timezone) {
                return [$timezone => static::displayTimezone($timezone)];
            })
            ->sortKeys()
            ->toArray();
    }

    public static function getTimezonesAsFlatList(): array
    {
        return \collect(self::getTimezones())->flatten()->toArray();
    }

    public static function displayTimezone(string $timezone): string
    {
        $offset = Carbon::now($timezone)->format('P');

        // Get localized timezone name
        $localizedTimezone = self::getLocalizedTimezoneName($timezone);

        $label = __('common.labels.timezone_format', [
            'timezone' => $localizedTimezone,
            'offset' => $offset,
        ]);

        return $label ?: "{$localizedTimezone} (UTC{$offset})";
    }

    protected static function getLocalizedTimezoneName(string $timezone): string
    {
        $timezoneTranslations = [
            'ar' => [
                'Africa/Cairo' => 'أفريقيا/القاهرة',
                'Asia/Riyadh' => 'آسيا/الرياض',
                'Asia/Dubai' => 'آسيا/دبي',
                'Asia/Kuwait' => 'آسيا/الكويت',
                'Asia/Qatar' => 'آسيا/قطر',
                'Asia/Bahrain' => 'آسيا/البحرين',
                'Asia/Baghdad' => 'آسيا/بغداد',
                'Asia/Damascus' => 'آسيا/دمشق',
                'Asia/Beirut' => 'آسيا/بيروت',
                'Asia/Amman' => 'آسيا/عمان',
                'Asia/Jerusalem' => 'آسيا/القدس',
                'Africa/Casablanca' => 'أفريقيا/الدار البيضاء',
                'Africa/Tunis' => 'أفريقيا/تونس',
                'Africa/Algiers' => 'أفريقيا/الجزائر',
                'Europe/London' => 'أوروبا/لندن',
                'Europe/Paris' => 'أوروبا/باريس',
                'America/New_York' => 'أمريكا/نيويورك',
                'America/Los_Angeles' => 'أمريكا/لوس أنجلوس',
            ],
        ];

        $locale = app()->getLocale();

        if (isset($timezoneTranslations[$locale][$timezone])) {
            return $timezoneTranslations[$locale][$timezone];
        }

        return $timezone;
    }
}
