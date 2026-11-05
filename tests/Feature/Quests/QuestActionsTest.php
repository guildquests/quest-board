<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;

test('accept claims an open quest', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create();

    $this->actingAs($user)->from(route('quests.show', $quest))
        ->post(route('quests.accept', $quest))
        ->assertRedirect(route('quests.show', $quest))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Mission accepted! Turn it in within 7 days.',
        ])
        ->assertInertiaFlash('mission_accepted', true);

    expect($quest->fresh()->status)->toBe(QuestStatus::InProgress)
        ->and($quest->fresh()->adventurer_id)->toBe($user->id);
});

test('accept is forbidden for the poster and for users with an active quest', function () {
    $quest = Quest::factory()->create();

    $this->actingAs($quest->poster)->post(route('quests.accept', $quest))->assertForbidden();

    $busy = User::factory()->create();
    Quest::factory()->inReview()->create(['adventurer_id' => $busy->id]);
    $this->actingAs($busy)->post(route('quests.accept', $quest))->assertForbidden();

    expect($quest->fresh()->status)->toBe(QuestStatus::Open);
});

test('accepting a quest someone else just took shows an error toast', function () {
    $quest = Quest::factory()->inProgress()->create();

    $this->actingAs(User::factory()->create())->from(route('quests.show', $quest))
        ->post(route('quests.accept', $quest))
        ->assertRedirect(route('quests.show', $quest))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Someone else just took this quest.'])
        ->assertInertiaFlashMissing('mission_accepted');
});

test('drop reopens the quest', function () {
    $quest = Quest::factory()->inProgress()->create();

    $this->actingAs($quest->adventurer)->post(route('quests.drop', $quest))->assertRedirect();

    expect($quest->fresh()->status)->toBe(QuestStatus::Open)
        ->and($quest->fresh()->adventurer_id)->toBeNull();
});

test('drop is forbidden for someone who is not the adventurer', function () {
    $quest = Quest::factory()->inProgress()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('quests.drop', $quest))
        ->assertForbidden();
});

test('turn in moves the quest to review', function () {
    $quest = Quest::factory()->inProgress()->create();

    $this->actingAs($quest->adventurer)
        ->post(route('quests.turn-in', $quest), ['result_url' => 'https://example.com/r', 'result_note' => 'Done'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $quest->refresh();

    expect($quest->status)->toBe(QuestStatus::InReview)
        ->and($quest->result_url)->toBe('https://example.com/r')
        ->and($quest->result_note)->toBe('Done');
});

test('turn in works with only a note', function () {
    $quest = Quest::factory()->inProgress()->create();

    $this->actingAs($quest->adventurer)
        ->post(route('quests.turn-in', $quest), ['result_url' => '', 'result_note' => 'Just words'])
        ->assertSessionHasNoErrors();

    expect($quest->fresh()->status)->toBe(QuestStatus::InReview)
        ->and($quest->fresh()->result_url)->toBeNull();
});

test('turn in validates the link and note', function (array $input, string $field) {
    $quest = Quest::factory()->inProgress()->create();

    $this->actingAs($quest->adventurer)
        ->post(route('quests.turn-in', $quest), $input)
        ->assertSessionHasErrors($field);

    expect($quest->fresh()->status)->toBe(QuestStatus::InProgress);
})->with([
    'neither' => [[], 'result_url'],
    'not a url' => [['result_url' => 'not a url'], 'result_url'],
    'wrong scheme' => [['result_url' => 'ftp://example.com/file'], 'result_url'],
    'note too long' => [['result_note' => str_repeat('a', 1001)], 'result_note'],
]);

test('a stale turn in redirects back with an error toast', function () {
    $quest = Quest::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('quests.show', $quest))
        ->post(route('quests.turn-in', $quest), ['result_note' => 'Late'])
        ->assertRedirect(route('quests.show', $quest))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'This quest is no longer in progress.']);

    expect($quest->fresh()->status)->toBe(QuestStatus::Open);
});

test('a stale approve by a lead redirects back with an error toast', function () {
    $quest = Quest::factory()->inProgress()->create();

    $this->actingAs(User::factory()->lead()->create())->from(route('quests.show', $quest))
        ->post(route('quests.approve', $quest))
        ->assertRedirect(route('quests.show', $quest))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'This quest is no longer in review.']);
});

test('send back needs a note and returns the quest to in progress', function () {
    $quest = Quest::factory()->inReview()->create();
    $lead = User::factory()->lead()->create();

    $this->actingAs($lead)->post(route('quests.send-back', $quest), ['note' => ''])
        ->assertSessionHasErrors('note');
    expect($quest->fresh()->status)->toBe(QuestStatus::InReview);

    $this->actingAs($lead)->post(route('quests.send-back', $quest), ['note' => 'Add sources'])
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => "Sent back to {$quest->adventurer->name}."]);

    expect($quest->fresh()->status)->toBe(QuestStatus::InProgress)
        ->and($quest->events()->latest('id')->first()->event)->toBe(ActivityEvent::SentBack);
});

test('approve finishes the quest with the chosen difficulty', function () {
    $poster = User::factory()->create(['name' => 'Poster']);
    $adventurer = User::factory()->create(['name' => 'Adventurer']);
    $quest = Quest::factory()->inReview()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
        'title' => 'Map the clash rules',
        'difficulty' => Difficulty::Easy,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('quests.approve', $quest), ['difficulty' => 'hard'])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => "Adventurer earned 60 XP for 'Map the clash rules'. Poster got +5 XP for posting.",
        ]);

    $quest->refresh();

    expect($quest->status)->toBe(QuestStatus::Done)
        ->and($quest->difficulty)->toBe(Difficulty::Hard)
        ->and($quest->xp)->toBe(60)
        ->and($quest->approved_by)->toBe($admin->id);
});

test('approve rejects an unknown difficulty and keeps the current one when blank', function () {
    $quest = Quest::factory()->inReview()->create(['difficulty' => Difficulty::Medium]);
    $lead = User::factory()->lead()->create();

    $this->actingAs($lead)->post(route('quests.approve', $quest), ['difficulty' => 'brutal'])
        ->assertSessionHasErrors('difficulty');

    $this->actingAs($lead)->post(route('quests.approve', $quest), ['difficulty' => ''])
        ->assertSessionHasNoErrors();

    expect($quest->fresh()->difficulty)->toBe(Difficulty::Medium)
        ->and($quest->fresh()->xp)->toBe(30);
});

test('members get 403 on send back and approve', function () {
    $quest = Quest::factory()->inReview()->create();
    $member = User::factory()->create();

    $this->actingAs($member)->post(route('quests.send-back', $quest), ['note' => 'No'])->assertForbidden();
    $this->actingAs($member)->post(route('quests.approve', $quest))->assertForbidden();

    expect($quest->fresh()->status)->toBe(QuestStatus::InReview);
});

test('a lead cannot approve their own turn in', function () {
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->inReview()->create(['adventurer_id' => $lead->id]);

    $this->actingAs($lead)->post(route('quests.approve', $quest))->assertForbidden();

    expect($quest->fresh()->status)->toBe(QuestStatus::InReview);
});

test('a lead cannot send back their own turn in', function () {
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->inReview()->create(['adventurer_id' => $lead->id]);

    $this->actingAs($lead)
        ->post(route('quests.send-back', $quest), ['note' => 'Needs work'])
        ->assertForbidden();

    expect($quest->fresh()->status)->toBe(QuestStatus::InReview);
});
