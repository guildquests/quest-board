<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Portraits
    |--------------------------------------------------------------------------
    |
    | The fixed set of portraits an admin can give a user, keyed by the value
    | stored in users.portrait. To add one, drop the image in
    | public/assets/ui/portraits/ and add a line here.
    |
    */

    'portraits' => [
        'portrait-01' => '/assets/ui/portraits/portrait-01.webp',
        'portrait-02' => '/assets/ui/portraits/portrait-02.webp',
        'portrait-03' => '/assets/ui/portraits/portrait-03.webp',
        'portrait-04' => '/assets/ui/portraits/portrait-04.webp',
        'portrait-05' => '/assets/ui/portraits/portrait-05.webp',
        'portrait-06' => '/assets/ui/portraits/portrait-06.webp',
        'portrait-07' => '/assets/ui/portraits/portrait-07.webp',
        'portrait-08' => '/assets/ui/portraits/portrait-08.webp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Levels
    |--------------------------------------------------------------------------
    |
    | Lifetime XP needed to reach each level, in ascending order, with the
    | title shown for it. The first entry is level 1.
    |
    */

    'levels' => [
        ['threshold' => 0, 'title' => 'Apprentice'],
        ['threshold' => 30, 'title' => 'Squire'],
        ['threshold' => 80, 'title' => 'Adventurer'],
        ['threshold' => 150, 'title' => 'Knight'],
        ['threshold' => 250, 'title' => 'Champion'],
        ['threshold' => 400, 'title' => 'Legend'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Difficulties
    |--------------------------------------------------------------------------
    |
    | XP an adventurer earns for an approved quest of each difficulty, and the
    | effort hint shown beside it. Keys match App\Enums\Difficulty values.
    |
    */

    'difficulties' => [
        'easy' => ['xp' => 10, 'effort' => '~1 hr'],
        'medium' => ['xp' => 30, 'effort' => '~half day'],
        'hard' => ['xp' => 60, 'effort' => '1–2 days'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Poster XP
    |--------------------------------------------------------------------------
    |
    | XP the poster earns when a quest they posted is approved.
    |
    */

    'poster_xp' => 5,

    /*
    |--------------------------------------------------------------------------
    | Claim days
    |--------------------------------------------------------------------------
    |
    | Days an adventurer has to turn in a quest before the claim expires and
    | the quest becomes open again.
    |
    */

    'claim_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Reward summary threshold
    |--------------------------------------------------------------------------
    |
    | More than this many unseen rewards are shown as one summary.
    |
    */

    'reward_summary_after' => 3,

    /*
    |--------------------------------------------------------------------------
    | Badges
    |--------------------------------------------------------------------------
    |
    | Done quests needed to earn each badge. Keys match App\Enums\Badge values.
    | Scout and Toolsmith count Research and Tooling quests; Explorer counts
    | distinct categories; Quest Giver counts posted quests that reached Done.
    |
    */

    'badges' => [
        'first_quest' => 1,
        'scout' => 3,
        'toolsmith' => 3,
        'quest_giver' => 5,
        'explorer' => 3,
    ],

];
