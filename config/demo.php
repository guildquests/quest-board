<?php

declare(strict_types=1);

return [
    // The reset command refuses to run unless this is enabled.
    'enabled' => (bool) env('DEMO_MODE', false),

    // Daily reset time in UTC, using HH:MM format.
    'reset_time' => env('DEMO_RESET_TIME', '00:00'),

    'password' => 'password',

    'accounts' => [
        'admin@example.com',
        'grace.marlow@example.com',
        'kit.thornton@example.com',
        'alan.brooks@example.com',
        'lin.torres@example.com',
    ],
];
