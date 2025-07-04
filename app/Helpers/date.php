<?php

declare(strict_types=1);
use App\Models\User;
use Carbon\Carbon;

if (! function_exists('toUserDate')) {
    function toUserDate(string|Carbon $date, User $user): string
    {

        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        return Carbon::parse($date, 'UTC')->setTimezone($user->timezone)->format('d/m/Y');
    }
}

if (! function_exists('toUserTime')) {
    function toUserTime(string|Carbon $date, User $user): string
    {

        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        $carbon = Carbon::parse($date, 'UTC')->setTimezone($user->timezone);

        return $carbon->format('g:i A');

    }
}

if (! function_exists('toUserDateTime')) {
    function toUserDateTime(string|Carbon $date, User $user): string
    {

        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        return Carbon::parse($date, 'UTC')->setTimezone($user->timezone)->format('d/m/Y g:i A');

    }
}

if (! function_exists('fromUserDate')) {
    function fromUserDate(string|Carbon $date, User $user): string
    {

        // Normalize Carbon objects to strings
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        // Parse the date string as being in the user's timezone, then convert to UTC
        return Carbon::parse($date, $user->timezone)->setTimezone('UTC')->toDateString();
    }
}

if (! function_exists('fromUserTime')) {
    function fromUserTime(string|Carbon $date, User $user): string
    {

        // Normalize Carbon objects to strings
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        // Parse the date string as being in the user's timezone, then convert to UTC
        return Carbon::parse($date, $user->timezone)->setTimezone('UTC')->toTimeString();
    }
}

if (! function_exists('fromUserDateTime')) {
    function fromUserDateTime(string|Carbon $date, User $user): string
    {

        // Normalize Carbon objects to strings
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        // Parse the date string as being in the user's timezone, then convert to UTC
        return Carbon::parse($date, $user->timezone)->setTimezone('UTC')->toDateTimeString();
    }
}
