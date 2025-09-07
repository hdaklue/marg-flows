<?php

declare(strict_types=1);

namespace App\Livewire;

use App\DTOs\Calendar\CalendarConfigDTO;
use Carbon\Carbon;
use Livewire\Component;

class CalendarTest extends Component
{
    public function render()
    {
        // Dummy configuration for testing
        $config = [
            'titleField' => 'title',
            'dateField' => 'startDate',
            'endDateField' => 'endDate',
            'colorField' => 'color',
            'defaultView' => 'month',
            'availableViews' => ['month', 'week', 'day'],
            'showWeekends' => true,
            'showNavigation' => true,
            'showToday' => true,
            'enableEventClick' => true,
            'timezone' => 'UTC',
        ];

        // Dummy events for testing
        $staticEvents = [
            [
                'id' => '1',
                'title' => 'Team Meeting',
                'startDate' => Carbon::now()->addDays(2)->toISOString(),
                'endDate' => Carbon::now()
                    ->addDays(2)
                    ->addHour()
                    ->toISOString(),
                'color' => 'sky-500',
                'allDay' => false,
                'meta' => [
                    'type' => 'meeting',
                    'attendees' => 5,
                    'location' => 'Conference Room A',
                ],
            ],
            [
                'id' => '2',
                'title' => 'Project Deadline',
                'startDate' => Carbon::now()->addDays(7)->toISOString(),
                'color' => 'red-500',
                'allDay' => true,
                'meta' => [
                    'type' => 'deadline',
                    'priority' => 'high',
                    'project' => 'KluePortal v2',
                ],
            ],
            [
                'id' => '3',
                'title' => 'Client Call',
                'startDate' => Carbon::now()->addDays(1)->toISOString(),
                'endDate' => Carbon::now()
                    ->addDays(1)
                    ->addMinutes(30)
                    ->toISOString(),
                'color' => 'emerald-500',
                'allDay' => false,
                'meta' => [
                    'type' => 'call',
                    'client' => 'ABC Corp',
                    'status' => 'confirmed',
                ],
            ],
            [
                'id' => '4',
                'title' => 'Code Review',
                'startDate' => Carbon::now()->addDays(3)->toISOString(),
                'color' => 'amber-500',
                'allDay' => true,
                'meta' => [
                    'type' => 'review',
                    'feature' => 'Calendar Component',
                    'reviewer' => 'Senior Dev',
                ],
            ],
            [
                'id' => '5',
                'title' => 'Design Workshop',
                'startDate' => Carbon::now()->addDays(5)->toISOString(),
                'endDate' => Carbon::now()
                    ->addDays(5)
                    ->addHours(3)
                    ->toISOString(),
                'color' => 'indigo-500',
                'allDay' => false,
                'meta' => [
                    'type' => 'workshop',
                    'topic' => 'UI/UX Best Practices',
                    'facilitator' => 'Design Team',
                ],
            ],
            [
                'id' => '6',
                'title' => 'Sprint Planning',
                'startDate' => Carbon::now()->subDays(1)->toISOString(),
                'color' => 'purple-500',
                'allDay' => true,
                'meta' => [
                    'type' => 'planning',
                    'sprint' => 'Sprint 23',
                    'duration' => '2 weeks',
                ],
            ],
            [
                'id' => '7',
                'title' => 'All Hands Meeting',
                'startDate' => Carbon::now()->addDays(10)->toISOString(),
                'endDate' => Carbon::now()
                    ->addDays(10)
                    ->addHour()
                    ->toISOString(),
                'color' => 'rose-500',
                'allDay' => false,
                'meta' => [
                    'type' => 'meeting',
                    'audience' => 'All Staff',
                    'agenda' => 'Q4 Updates',
                ],
            ],
            [
                'id' => '8',
                'title' => 'Lunch & Learn',
                'startDate' => Carbon::now()->addDays(4)->toISOString(),
                'color' => 'green-500',
                'allDay' => true,
                'meta' => [
                    'type' => 'learning',
                    'topic' => 'Laravel Performance',
                    'presenter' => 'Tech Lead',
                ],
            ],
        ];

        return view('livewire.calendar-test', [
            'config' => $config,
            'events' => $staticEvents,
        ]);
    }
}
