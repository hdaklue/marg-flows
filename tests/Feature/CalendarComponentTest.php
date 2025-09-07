<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\Calendar\CalendarConfigDTO;
use App\DTOs\Calendar\CalendarEventDTO;
use App\Livewire\Components\Calendar\CalendarComponent;
use App\Models\Document;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CalendarComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_component_renders_successfully(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
                'defaultView' => 'month',
                'availableViews' => ['month', 'week', 'day'],
            ],
            'staticEvents' => [
                [
                    'id' => '1',
                    'title' => 'Test Event',
                    'startDate' => now()->toISOString(),
                ],
            ],
        ])
            ->assertOk()
            ->assertSee('Test Event');
    }

    public function test_calendar_config_dto_validation(): void
    {
        $validConfig = [
            'titleField' => 'title',
            'dateField' => 'created_at',
            'defaultView' => 'month',
            'availableViews' => ['month', 'week', 'day'],
            'showWeekends' => true,
            'showNavigation' => true,
            'showToday' => true,
            'enableEventClick' => true,
        ];

        $config = CalendarConfigDTO::fromArray($validConfig);

        $this->assertEquals('title', $config->titleField);
        $this->assertEquals('created_at', $config->dateField);
        $this->assertEquals('month', $config->defaultView);
        $this->assertTrue($config->showWeekends);
        $this->assertTrue($config->enableEventClick);
    }

    public function test_calendar_event_dto_creation(): void
    {
        $eventData = [
            'id' => 'test-event-1',
            'title' => 'Test Event',
            'startDate' => now(),
            'endDate' => now()->addHour(),
            'color' => 'sky-500',
            'meta' => ['type' => 'meeting'],
            'allDay' => false,
        ];

        $event = CalendarEventDTO::fromArray($eventData);

        $this->assertEquals('test-event-1', $event->id);
        $this->assertEquals('Test Event', $event->title);
        $this->assertEquals('sky-500', $event->color);
        $this->assertFalse($event->allDay);
        $this->assertArrayHasKey('type', $event->meta);
    }

    public function test_calendar_event_dto_methods(): void
    {
        $startDate = Carbon::parse('2024-01-15 10:00:00');
        $endDate = Carbon::parse('2024-01-17 15:00:00');

        $event = CalendarEventDTO::fromArray([
            'id' => 'test',
            'title' => 'Multi-day Event',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'allDay' => true,
        ]);

        $this->assertTrue($event->isMultiDay());
        $this->assertEquals(3, $event->getDurationInDays());
        $this->assertTrue($event->occursOnDate(Carbon::parse('2024-01-16')));
        $this->assertFalse($event->occursOnDate(Carbon::parse('2024-01-18')));
    }

    public function test_calendar_component_view_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
                'defaultView' => 'month',
                'availableViews' => ['month', 'week', 'day'],
            ],
        ])
            ->assertSet('currentView', 'month')
            ->call('changeView', 'week')
            ->assertSet('currentView', 'week')
            ->assertDispatched('calendar:view-changed')
            ->call('changeView', 'day')
            ->assertSet('currentView', 'day');
    }

    public function test_calendar_component_navigation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $initialDate = Carbon::parse('2024-01-15');

        Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
                'defaultView' => 'month',
            ],
        ])
            ->set('currentDate', $initialDate)
            ->call('goToNext')
            ->assertDispatched('calendar:date-changed')
            ->call('goToPrevious')
            ->assertDispatched('calendar:date-changed')
            ->call('goToToday')
            ->assertDispatched('calendar:date-changed');
    }

    public function test_calendar_component_with_model_events(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test documents
        $documents = Document::factory()
            ->count(3)
            ->create([
                'name' => 'Test Document',
                'created_at' => now(),
                'tenant_id' => $user->current_team_id,
            ]);

        Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'name',
                'dateField' => 'created_at',
                'defaultView' => 'month',
            ],
            'modelClass' => Document::class,
            'events' => $documents,
        ])->assertOk();

        // Test that events are properly computed
        $component = Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'name',
                'dateField' => 'created_at',
            ],
            'events' => $documents,
        ]);

        $currentEvents = $component->get('currentEvents');
        $this->assertCount(3, $currentEvents);
        $this->assertEquals('Test Document', $currentEvents[0]['title']);
    }

    public function test_calendar_component_event_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
                'enableEventClick' => true,
            ],
            'staticEvents' => [
                [
                    'id' => 'test-event',
                    'title' => 'Clickable Event',
                    'startDate' => now()->toISOString(),
                ],
            ],
        ])->call('selectEvent', 'test-event')->assertDispatched(
            'calendar:event-selected',
            eventId: 'test-event',
        );
    }

    public function test_calendar_component_date_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $testDate = '2024-01-15';

        Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
            ],
        ])->call('selectDate', $testDate)->assertDispatched(
            'calendar:date-selected',
            date: Carbon::parse($testDate)->toISOString(),
        );
    }

    public function test_calendar_component_month_view_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
                'defaultView' => 'month',
            ],
        ]);

        $calendarData = $component->get('calendarData');

        $this->assertEquals('month', $calendarData['type']);
        $this->assertArrayHasKey('weeks', $calendarData);
        $this->assertArrayHasKey('weekdays', $calendarData);
        $this->assertIsArray($calendarData['weeks']);
    }

    public function test_calendar_component_week_view_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
                'defaultView' => 'week',
            ],
        ]);

        $calendarData = $component->get('calendarData');

        $this->assertEquals('week', $calendarData['type']);
        $this->assertArrayHasKey('days', $calendarData);
        $this->assertIsArray($calendarData['days']);
        $this->assertCount(7, $calendarData['days']);
    }

    public function test_calendar_component_day_view_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
                'defaultView' => 'day',
            ],
        ]);

        $calendarData = $component->get('calendarData');

        $this->assertEquals('day', $calendarData['type']);
        $this->assertArrayHasKey('date', $calendarData);
        $this->assertArrayHasKey('events', $calendarData);
        $this->assertArrayHasKey('dayName', $calendarData);
    }

    public function test_calendar_event_color_methods(): void
    {
        // Test Tailwind color
        $event1 = CalendarEventDTO::fromArray([
            'id' => '1',
            'title' => 'Event 1',
            'startDate' => now(),
            'color' => 'emerald-500',
        ]);

        $this->assertEquals(
            'bg-emerald-500 text-white',
            $event1->getColorClass(),
        );
        $this->assertEquals('', $event1->getInlineStyle());

        // Test hex color
        $event2 = CalendarEventDTO::fromArray([
            'id' => '2',
            'title' => 'Event 2',
            'startDate' => now(),
            'color' => '#ff5722',
        ]);

        $this->assertEquals('', $event2->getColorClass());
        $this->assertEquals(
            'background-color: #ff5722; color: white;',
            $event2->getInlineStyle(),
        );

        // Test default color
        $event3 = CalendarEventDTO::fromArray([
            'id' => '3',
            'title' => 'Event 3',
            'startDate' => now(),
        ]);

        $this->assertEquals('bg-sky-500 text-white', $event3->getColorClass());
        $this->assertEquals('', $event3->getInlineStyle());
    }

    public function test_calendar_component_refresh_events(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CalendarComponent::class, [
            'configArray' => [
                'titleField' => 'title',
                'dateField' => 'created_at',
            ],
        ])
            ->call('refreshEvents')
            ->assertDispatched('calendar:events-refreshed');
    }

    public function test_calendar_config_dto_defaults(): void
    {
        $config = CalendarConfigDTO::fromArray([
            'titleField' => 'title',
            'dateField' => 'created_at',
        ]);

        $this->assertEquals('month', $config->defaultView);
        $this->assertEquals(['month', 'week', 'day'], $config->availableViews);
        $this->assertTrue($config->showWeekends);
        $this->assertTrue($config->showNavigation);
        $this->assertTrue($config->showToday);
        $this->assertTrue($config->enableEventClick);
        $this->assertEquals([], $config->restrictions);
    }

    public function test_calendar_config_dto_with_restrictions(): void
    {
        $config = CalendarConfigDTO::fromArray([
            'titleField' => 'title',
            'dateField' => 'created_at',
            'restrictions' => [
                'minDate' => '2024-01-01',
                'maxDate' => '2024-12-31',
                'disabledDates' => ['2024-12-25', '2024-01-01'],
            ],
        ]);

        $this->assertArrayHasKey('minDate', $config->restrictions);
        $this->assertArrayHasKey('maxDate', $config->restrictions);
        $this->assertArrayHasKey('disabledDates', $config->restrictions);
        $this->assertEquals('2024-01-01', $config->restrictions['minDate']);
        $this->assertEquals('2024-12-31', $config->restrictions['maxDate']);
        $this->assertContains(
            '2024-12-25',
            $config->restrictions['disabledDates'],
        );
    }
}
