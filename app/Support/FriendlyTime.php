<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

final class FriendlyTime
{
    public static function ago(CarbonInterface $date, ?CarbonInterface $now = null): string
    {
        $timezone = (string) config('app.display_timezone');
        $localDate = $date->setTimezone($timezone);
        $localNow = ($now ?? now($timezone))->setTimezone($timezone);
        $today = $localNow->startOfDay();
        $daysAgo = (int) $localDate->startOfDay()->diffInDays($today);

        return match (true) {
            $daysAgo === 0 => 'Today',
            $daysAgo === 1 => 'Yesterday',
            $daysAgo >= 2 && $daysAgo <= 6 => $daysAgo.' days ago',
            $localDate->year === $today->year => $localDate->format('j M'),
            default => $localDate->format('j M Y'),
        };
    }

    public static function date(CarbonInterface $date): string
    {
        return $date
            ->setTimezone((string) config('app.display_timezone'))
            ->format('j M');
    }
}
