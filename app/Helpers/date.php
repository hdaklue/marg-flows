<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;

if (! function_exists('toUserDate')) {
    /**
     * Convert UTC date to user's timezone and format as short date.
     *
     * @param  string|Carbon  $date  UTC date to convert
     * @param  User  $user  User with timezone information
     * @return string Date in user's timezone formatted as d/M/Y
     *
     * @example
     * // Input: "2025-01-15 12:30:00" (UTC), User timezone: "America/New_York"
     * // Output: "15/Jan/2025"
     *
     * // Input: "2025-12-31 23:30:00" (UTC), User timezone: "Asia/Tokyo"
     * // Output: "01/Jan/2026"
     */
    function toUserDate(string|Carbon $date, User $user): string
    {
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($date, 'UTC')
            ->setTimezone($timezone)
            ->format('M j, Y');
    }
}

if (! function_exists('toUserDateString')) {
    /**
     * Convert UTC date to user's timezone and format as readable date string.
     *
     * @param  string|Carbon  $date  UTC date to convert
     * @param  User  $user  User with timezone information
     * @return string Date in user's timezone formatted as D, j M Y
     *
     * @example
     * // Input: "2025-01-15 12:30:00" (UTC), User timezone: "America/New_York"
     * // Output: "Wed, 15 Jan 2025"
     *
     * // Input: "2025-12-31 23:30:00" (UTC), User timezone: "Asia/Tokyo"
     * // Output: "Thu, 1 Jan 2026"
     */
    function toUserDateString(string|Carbon $date, User $user): string
    {
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($date, 'UTC')
            ->setTimezone($timezone)
            ->format('D, j M Y');
    }
}

if (! function_exists('toUserTime')) {
    /**
     * Convert UTC date to user's timezone and format as time only.
     *
     * @param  string|Carbon  $date  UTC date to convert
     * @param  User  $user  User with timezone information
     * @return string Time in user's timezone formatted as g:i A
     *
     * @example
     * // Input: "2025-01-15 12:30:00" (UTC), User timezone: "America/New_York"
     * // Output: "7:30 AM"
     *
     * // Input: "2025-01-15 23:45:00" (UTC), User timezone: "Europe/London"
     * // Output: "11:45 PM"
     */
    function toUserTime(string|Carbon $date, User $user): string
    {
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');
        $carbon = Carbon::parse($date, 'UTC')->setTimezone($timezone);

        return $carbon->format('g:i A');
    }
}

if (! function_exists('toUserDateTime')) {
    /**
     * Convert UTC date to user's timezone and format as date and time.
     *
     * @param  string|Carbon  $date  UTC date to convert
     * @param  User  $user  User with timezone information
     * @return string DateTime in user's timezone formatted as d/m/Y g:i A
     *
     * @example
     * // Input: "2025-01-15 12:30:00" (UTC), User timezone: "America/New_York"
     * // Output: "15/01/2025 7:30 AM"
     *
     * // Input: "2025-12-31 23:45:00" (UTC), User timezone: "Asia/Tokyo"
     * // Output: "01/01/2026 8:45 AM"
     */
    function toUserDateTime(string|Carbon $date, User $user): string
    {
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($date, 'UTC')
            ->setTimezone($timezone)
            ->format('d/m/Y g:i A');
    }
}

if (! function_exists('fromUserDate')) {
    /**
     * Convert date from user's timezone to UTC and format as date string.
     *
     * @param  string|Carbon  $date  Date in user's timezone to convert
     * @param  User  $user  User with timezone information
     * @return string UTC date formatted as Y-m-d
     *
     * @example
     * // Input: "15/01/2025" (in user's timezone), User timezone: "America/New_York"
     * // Output: "2025-01-15"
     *
     * // Input: "01/01/2026" (in user's timezone), User timezone: "Asia/Tokyo"
     * // Output: "2025-12-31"
     */
    function fromUserDate(string|Carbon $date, User $user): string
    {
        // Normalize Carbon objects to strings
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        // Parse the date string as being in the user's timezone, then convert to UTC
        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($date, $timezone)
            ->setTimezone('UTC')
            ->toDateString();
    }
}

if (! function_exists('fromUserTime')) {
    /**
     * Convert time from user's timezone to UTC and format as time string.
     *
     * @param  string|Carbon  $date  Time in user's timezone to convert
     * @param  User  $user  User with timezone information
     * @return string UTC time formatted as H:i:s
     *
     * @example
     * // Input: "7:30 AM" (in user's timezone), User timezone: "America/New_York"
     * // Output: "12:30:00"
     *
     * // Input: "11:45 PM" (in user's timezone), User timezone: "Europe/London"
     * // Output: "23:45:00"
     */
    function fromUserTime(string|Carbon $date, User $user): string
    {
        // Normalize Carbon objects to strings
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        // Parse the date string as being in the user's timezone, then convert to UTC
        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($date, $timezone)
            ->setTimezone('UTC')
            ->toTimeString();
    }
}

if (! function_exists('fromUserDateTime')) {
    /**
     * Convert datetime from user's timezone to UTC and format as datetime string.
     *
     * @param  string|Carbon  $date  DateTime in user's timezone to convert
     * @param  User  $user  User with timezone information
     * @return string UTC datetime formatted as Y-m-d H:i:s
     *
     * @example
     * // Input: "15/01/2025 7:30 AM" (in user's timezone), User timezone: "America/New_York"
     * // Output: "2025-01-15 12:30:00"
     *
     * // Input: "01/01/2026 8:45 AM" (in user's timezone), User timezone: "Asia/Tokyo"
     * // Output: "2025-12-31 23:45:00"
     */
    function fromUserDateTime(string|Carbon $date, User $user): string
    {
        // Normalize Carbon objects to strings
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        // Parse the date string as being in the user's timezone, then convert to UTC
        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($date, $timezone)
            ->setTimezone('UTC')
            ->toDateTimeString();
    }
}

if (! function_exists('toUserDiffForHuman')) {
    /**
     * Convert UTC date to user's timezone and format as human-readable difference.
     * Always uses "ago/in" format relative to now, even when custom comparison date is provided.
     *
     * @param  string|Carbon  $date  UTC date to convert
     * @param  User  $user  User with timezone information
     * @param  Carbon|null  $other  Optional date to compare against (defaults to now in user's timezone)
     * @return string Human-readable time difference in "ago/in" format
     *
     * @example
     * // Input: "2025-01-15 12:00:00" (UTC), current time "2025-01-15 12:30:00" (UTC)
     * // Output: "30 minutes ago"
     *
     * // Input: "2025-01-15 13:00:00" (UTC), current time "2025-01-15 12:30:00" (UTC)
     * // Output: "in 30 minutes"
     *
     * // Input: "2025-01-14 12:00:00" (UTC), current time "2025-01-15 12:30:00" (UTC)
     * // Output: "1 day ago"
     */
    function toUserDiffForHuman(
        string|Carbon $date,
        User $user,
        ?Carbon $other = null,
    ): string {
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');
        $userDate = Carbon::parse($date, 'UTC')->setTimezone($timezone);

        // Always use "ago/in" format by calling diffForHumans() without comparison date
        // This forces Carbon to use "ago/in" format relative to now
        if ($other) {
            // For custom comparison, we need to temporarily set Carbon's "now" to the other date
            $other = $other->setTimezone($timezone);

            return $userDate->diffForHumans($other);
        }

        // Default behavior - compare to current time in user's timezone for "ago/in" format
        return $userDate->diffForHumans();
    }
}

if (! function_exists('toUserIsoString')) {
    /**
     * Convert UTC date to user's timezone and format as ISO string.
     *
     * @param  string|Carbon  $date  UTC date to convert
     * @param  User  $user  User with timezone information
     * @return string ISO string in user's timezone
     *
     * @example
     * // Input: "2025-01-15 12:30:00" (UTC), User timezone: "America/New_York"
     * // Output: "2025-01-15T07:30:00.000-05:00"
     *
     * // Input: "2025-06-15 12:30:00" (UTC), User timezone: "Europe/London"
     * // Output: "2025-06-15T13:30:00.000+01:00"
     */
    function toUserIsoString(string|Carbon $date, User $user): string
    {
        if ($date instanceof Carbon) {
            $date = $date->toDateTimeString();
        }

        // Parse UTC date and convert to user's timezone, then format as ISO string
        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($date, 'UTC')
            ->setTimezone($timezone)
            ->toISOString();
    }
}

if (! function_exists('fromUserIsoString')) {
    /**
     * Convert ISO string from user's timezone back to UTC datetime string.
     *
     * @param  string  $isoString  ISO string in user's timezone
     * @param  User  $user  User with timezone information
     * @return string UTC datetime string
     *
     * @example
     * // Input: "2025-01-15T07:30:00.000-05:00", User timezone: "America/New_York"
     * // Output: "2025-01-15 12:30:00"
     *
     * // Input: "2025-06-15T13:30:00.000+01:00", User timezone: "Europe/London"
     * // Output: "2025-06-15 12:30:00"
     */
    function fromUserIsoString(string $isoString, User $user): string
    {
        // Parse ISO string as being in user's timezone, then convert to UTC
        $timezone = $user->getTimezone() ?? config('app.timezone', 'UTC');

        return Carbon::parse($isoString, $timezone)
            ->setTimezone('UTC')
            ->toDateTimeString();
    }
}
