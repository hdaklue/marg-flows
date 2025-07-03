<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class Timezone
{
    public static function getTimezones(): array
    {
        return Cache::rememberForever('app:time-zones', function () {
            // return collect(countries(hydrate: true))->map(fn ($item) => $item->getTimezones())
            //     ->flatten()->mapWithKeys(fn ($item) => [$item => $item])
            //     ->sort()->toArray();
            return collect(countries(hydrate: true))->flatten()->mapWithKeys(fn ($item) => [$item->getName() => $item->getTimezones()])->map(fn ($value, $key) => $value)->sortKeys()->toArray();
        });
    }

    public static function getTimezonesAsSelectList(): array
    {
        return collect(static::getTimezones())
            ->flatten(1)
            ->mapWithKeys(fn ($timezone) => [$timezone => $timezone])->sortKeys()->toArray();
    }

    public static function getTimezonesAsFlatList(): array
    {
        return \collect(static::getTimezones())->flatten()->toArray();
    }
}
