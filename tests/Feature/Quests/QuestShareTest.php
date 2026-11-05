<?php

declare(strict_types=1);

use App\Models\Quest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the quest page includes share data for every role and status', function (string $role, string $status): void {
    $viewer = $role === 'lead'
        ? User::factory()->lead()->create()
        : User::factory()->create();

    $quest = match ($status) {
        'open' => Quest::factory()->create(),
        'in_progress' => Quest::factory()->inProgress()->create(),
        'in_review' => Quest::factory()->inReview()->create(),
        'done' => Quest::factory()->done()->create(),
    };
    $publicUrl = route('quests.public', ['publicId' => $quest->public_id]);

    $this->actingAs($viewer)
        ->get(route('quests.show', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->where('share.public_url', $publicUrl)
            ->where('share.qr', fn (string $qr): bool => str_starts_with(
                $qr,
                'data:image/svg+xml;base64,',
            )));
})->with([
    'member open' => ['member', 'open'],
    'member in progress' => ['member', 'in_progress'],
    'member in review' => ['member', 'in_review'],
    'member done' => ['member', 'done'],
    'lead open' => ['lead', 'open'],
    'lead in progress' => ['lead', 'in_progress'],
    'lead in review' => ['lead', 'in_review'],
    'lead done' => ['lead', 'done'],
]);
