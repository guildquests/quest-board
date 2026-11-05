<?php

declare(strict_types=1);

/*
 * Branding defaults and theme presets. The settings table (edited by admins)
 * wins; the defaults are used to create the first settings row, and admins
 * change them in Settings > Branding.
 *
 * Presets are dark only. Each one fills the same colour tokens that
 * resources/css/app.css declares, and the active preset is injected into
 * <head> by resources/views/app.blade.php.
 */
return [
    'defaults' => [
        'name' => 'Quest Board',
        'tagline' => 'Post quests. Claim them. Earn XP.',
        'theme' => 'zinc-violet',
    ],

    // Shipped neutral art, used until an admin uploads their own.
    'assets' => [
        'logo' => '/assets/brand/logo.webp',
        'banner' => '/assets/brand/banner.webp',
        'header' => '/assets/brand/header.webp',
        'mascots' => [
            'login' => '/assets/brand/mascot-login.webp',
            'empty' => '/assets/brand/mascot-empty.webp',
            'reward' => '/assets/brand/mascot-reward.webp',
        ],
    ],

    'default_preset' => 'zinc-violet',

    'presets' => [
        'zinc-violet' => [
            'label' => 'Zinc and violet',
            'colors' => [
                'bg' => '#09090b',
                'panel' => '#111114',
                'panel-2' => '#18181c',
                'line' => '#2a2a31',
                'text' => '#ececf1',
                'muted-text' => '#a1a1ad',
                'accent' => '#9a8cf5',
                'accent-deep' => '#3b2a78',
                'primary' => '#8b5cf6',
                'primary-soft' => '#c4b5fd',
                'primary-shadow' => '#2e1a66',
                'on-primary' => '#14082e',
            ],
        ],
        'slate-indigo' => [
            'label' => 'Slate and indigo',
            'colors' => [
                'bg' => '#070b14',
                'panel' => '#0e1424',
                'panel-2' => '#131b30',
                'line' => '#243049',
                'text' => '#e6ebf5',
                'muted-text' => '#9aa7c0',
                'accent' => '#7aa7f7',
                'accent-deep' => '#2b3a8c',
                'primary' => '#818cf8',
                'primary-soft' => '#c7d2fe',
                'primary-shadow' => '#1e2260',
                'on-primary' => '#0f1233',
            ],
        ],
        'stone-amber' => [
            'label' => 'Stone and amber',
            'colors' => [
                'bg' => '#0c0a09',
                'panel' => '#171412',
                'panel-2' => '#1f1b18',
                'line' => '#3a332d',
                'text' => '#f1ece6',
                'muted-text' => '#b0a69b',
                'accent' => '#c9926a',
                'accent-deep' => '#6b4a1f',
                'primary' => '#f59e0b',
                'primary-soft' => '#fcd34d',
                'primary-shadow' => '#5a3300',
                'on-primary' => '#2a1700',
            ],
        ],
        'accent-primary' => [
            'label' => 'Emerald and primary',
            'colors' => [
                'bg' => '#05120e',
                'panel' => '#0a1d17',
                'panel-2' => '#0d241c',
                'line' => '#1d3a30',
                'text' => '#e7efe9',
                'muted-text' => '#9db3a8',
                'accent' => '#34c48a',
                'accent-deep' => '#0f5a3c',
                'primary' => '#e8b74a',
                'primary-soft' => '#f3d27f',
                'primary-shadow' => '#5a3d0c',
                'on-primary' => '#2a1c04',
            ],
        ],
    ],
];
