<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('quests:heartbeat')->everyMinute();
Schedule::command('quests:expire-claims')->daily();

if (config('demo.enabled')) {
    Schedule::command('quests:demo-reset')->dailyAt((string) config('demo.reset_time'));
}
