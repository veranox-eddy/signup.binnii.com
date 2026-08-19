<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    private function eventFor(string $command): Event
    {
        foreach (app(Schedule::class)->events() as $event) {
            if (str_contains((string) $event->command, $command)) {
                return $event;
            }
        }

        $this->fail("No scheduled event found for [{$command}].");
    }

    public function test_all_three_commands_are_scheduled_with_the_agreed_cadence(): void
    {
        $this->assertSame('* * * * *', $this->eventFor('signup:push --once')->expression);
        $this->assertSame('0 * * * *', $this->eventFor('signup:pull-markets')->expression);
        $this->assertSame('10 4 * * *', $this->eventFor('signup:purge')->expression);
    }

    public function test_push_deliberately_runs_without_an_overlap_mutex(): void
    {
        // withoutOverlapping() takes a cache lock and this app has no cache
        // table; signup:push is concurrency-safe by design (pushable() flips
        // rows to `pushing` first, and the api intake is idempotent on uuid).
        $this->assertFalse($this->eventFor('signup:push --once')->withoutOverlapping);
    }

    public function test_every_event_appends_output_to_the_schedule_log(): void
    {
        $expected = storage_path('logs/signup-schedule.log');

        foreach (['signup:push --once', 'signup:pull-markets', 'signup:purge'] as $command) {
            $this->assertSame($expected, $this->eventFor($command)->output);
        }
    }
}
