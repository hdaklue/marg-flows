<?php

declare(strict_types=1);

namespace App\DTOs\Calendar;

use WendellAdriel\ValidatedDTO\Casting\ArrayCast;
use WendellAdriel\ValidatedDTO\Casting\BooleanCast;
use WendellAdriel\ValidatedDTO\Casting\StringCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

/**
 * Configuration DTO for Calendar Component.
 *
 * @property string $titleField The field name to use for event titles
 * @property string $dateField The field name to use for event dates
 * @property string|null $endDateField Optional field name for event end dates
 * @property string|null $colorField Optional field name for event colors
 * @property string $defaultView Default view mode (month, week, day)
 * @property array $availableViews Array of enabled view modes
 * @property bool $showWeekends Whether to show weekends in calendar
 * @property bool $showNavigation Whether to show navigation buttons
 * @property bool $showToday Whether to show today button
 * @property bool $enableEventClick Whether events are clickable
 * @property string|null $timezone Timezone for date calculations
 * @property array $restrictions Date range restrictions
 */
final class CalendarConfigDTO extends ValidatedDTO
{
    public string $titleField;

    public string $dateField;

    public null|string $endDateField;

    public null|string $colorField;

    public string $defaultView;

    public array $availableViews;

    public bool $showWeekends;

    public bool $showNavigation;

    public bool $showToday;

    public bool $enableEventClick;

    public null|string $timezone;

    public array $restrictions;

    protected function rules(): array
    {
        return [
            'titleField' => ['required', 'string', 'max:50'],
            'dateField' => ['required', 'string', 'max:50'],
            'endDateField' => ['nullable', 'string', 'max:50'],
            'colorField' => ['nullable', 'string', 'max:50'],
            'defaultView' => ['required', 'string', 'in:month,week,day'],
            'availableViews' => ['required', 'array', 'min:1'],
            'availableViews.*' => ['required', 'string', 'in:month,week,day'],
            'showWeekends' => ['required', 'boolean'],
            'showNavigation' => ['required', 'boolean'],
            'showToday' => ['required', 'boolean'],
            'enableEventClick' => ['required', 'boolean'],
            'timezone' => ['nullable', 'string', 'timezone'],
            'restrictions' => ['array'],
            'restrictions.minDate' => ['nullable', 'date'],
            'restrictions.maxDate' => ['nullable', 'date'],
            'restrictions.disabledDates' => ['nullable', 'array'],
            'restrictions.disabledDates.*' => ['date'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'endDateField' => null,
            'colorField' => null,
            'defaultView' => 'month',
            'availableViews' => ['month', 'week', 'day'],
            'showWeekends' => true,
            'showNavigation' => true,
            'showToday' => true,
            'enableEventClick' => true,
            'timezone' => null,
            'restrictions' => [],
        ];
    }

    protected function casts(): array
    {
        return [
            'titleField' => new StringCast(),
            'dateField' => new StringCast(),
            'endDateField' => new StringCast(),
            'colorField' => new StringCast(),
            'defaultView' => new StringCast(),
            'availableViews' => new ArrayCast(),
            'showWeekends' => new BooleanCast(),
            'showNavigation' => new BooleanCast(),
            'showToday' => new BooleanCast(),
            'enableEventClick' => new BooleanCast(),
            'timezone' => new StringCast(),
            'restrictions' => new ArrayCast(),
        ];
    }
}
