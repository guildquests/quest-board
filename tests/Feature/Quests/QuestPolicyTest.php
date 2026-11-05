<?php

declare(strict_types=1);

use App\Models\Quest;
use App\Models\User;

test('poster and admin can update and delete only while open', function () {
    $quest = Quest::factory()->create();
    $admin = User::factory()->admin()->create();
    $lead = User::factory()->lead()->create();

    expect($quest->poster->can('update', $quest))->toBeTrue()
        ->and($quest->poster->can('delete', $quest))->toBeTrue()
        ->and($admin->can('update', $quest))->toBeTrue()
        ->and($admin->can('delete', $quest))->toBeTrue()
        ->and($lead->can('update', $quest))->toBeFalse()
        ->and(User::factory()->create()->can('delete', $quest))->toBeFalse();

    $claimed = Quest::factory()->inProgress()->create();

    expect($claimed->poster->can('update', $claimed))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('delete', $claimed))->toBeFalse();
});

test('accept is denied for the poster, busy users, and non-open quests', function () {
    $quest = Quest::factory()->create();
    $user = User::factory()->create();

    expect($user->can('accept', $quest))->toBeTrue()
        ->and($quest->poster->can('accept', $quest))->toBeFalse();

    Quest::factory()->inProgress()->create(['adventurer_id' => $user->id]);
    expect($user->can('accept', $quest))->toBeFalse();

    $reviewer = User::factory()->create();
    Quest::factory()->inReview()->create(['adventurer_id' => $reviewer->id]);
    expect($reviewer->can('accept', $quest))->toBeFalse();

    $taken = Quest::factory()->inProgress()->create();
    expect(User::factory()->create()->can('accept', $taken))->toBeFalse();
});

test('only the adventurer can drop or turn in an in-progress quest', function () {
    $quest = Quest::factory()->inProgress()->create();
    $adventurer = $quest->adventurer;

    expect($adventurer->can('drop', $quest))->toBeTrue()
        ->and($adventurer->can('turnIn', $quest))->toBeTrue()
        ->and(User::factory()->admin()->create()->can('drop', $quest))->toBeFalse()
        ->and($quest->poster->can('turnIn', $quest))->toBeFalse();

    $review = Quest::factory()->inReview()->create();
    expect($review->adventurer->can('drop', $review))->toBeFalse()
        ->and($review->adventurer->can('turnIn', $review))->toBeFalse();
});

test('leads and admins review in-review quests, members do not', function () {
    $quest = Quest::factory()->inReview()->create();

    foreach (['lead', 'admin'] as $role) {
        $reviewer = User::factory()->{$role}()->create();
        expect($reviewer->can('sendBack', $quest))->toBeTrue()
            ->and($reviewer->can('approve', $quest))->toBeTrue();
    }

    $member = User::factory()->create();
    expect($member->can('sendBack', $quest))->toBeFalse()
        ->and($member->can('approve', $quest))->toBeFalse();

    $progress = Quest::factory()->inProgress()->create();
    expect(User::factory()->lead()->create()->can('approve', $progress))->toBeFalse()
        ->and(User::factory()->lead()->create()->can('sendBack', $progress))->toBeFalse();
});

test('an adventurer who is a lead cannot review their own turn-in', function () {
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->inReview()->create(['adventurer_id' => $lead->id]);

    expect($lead->can('sendBack', $quest))->toBeFalse()
        ->and($lead->can('approve', $quest))->toBeFalse();
});

test('a lead can approve a quest they posted', function () {
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->inReview()->create(['poster_id' => $lead->id]);

    expect($lead->can('approve', $quest))->toBeTrue();
});
