<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserBadge;
use Inertia\Testing\AssertableInertia as Assert;

function sharePublicProfile(User $user): User
{
    $user->forceFill(['public_profile_enabled' => true])->saveOrFail();

    return $user->fresh();
}

test('guests see an enabled public member profile', function () {
    $user = sharePublicProfile(User::factory()->withPortrait('portrait-03')->create([
        'name' => 'Ada Quill',
    ]));

    $this->get(route('guild.member', ['publicId' => $user->public_id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/member')
            ->has('member')
            ->where('member.name', 'Ada Quill')
            ->where('member.portrait_url', '/assets/ui/portraits/portrait-03.webp')
            ->where('member.level', 1)
            ->where('member.level_title', 'Apprentice')
            ->where('member.xp', 0)
            ->where('member.quests_done', 0)
            ->where('member.badges', [])
            ->where('member.joined_year', (int) $user->created_at?->setTimezone(config('app.display_timezone'))->format('Y'))
            ->where('qr', fn (string $qr): bool => str_starts_with($qr, 'data:image/svg+xml;base64,')));
});

test('unknown profiles are unavailable', function () {
    $this->get('/guild/m/0123456789abcdef01234567')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/member-unavailable'));
});

test('malformed profile ids are unavailable', function () {
    $this->get('/guild/m/not-a-public-id')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/member-unavailable'));
});

test('profiles with sharing off are unavailable', function () {
    $user = User::factory()->create();

    $this->get(route('guild.member', ['publicId' => $user->public_id]))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/member-unavailable'));
});

test('deactivated profiles are unavailable', function () {
    $user = sharePublicProfile(User::factory()->create());
    $user->forceFill(['deactivated_at' => now()])->save();

    $this->get(route('guild.member', ['publicId' => $user->public_id]))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/member-unavailable'));
});

test('the public summary includes no private fields and only earned badges', function () {
    $user = User::factory()->create([
        'name' => 'Public Adventurer',
    ]);
    Quest::factory()->done()->create([
        'adventurer_id' => $user->id,
        'difficulty' => Difficulty::Medium,
    ]);
    Quest::factory()->inProgress()->create(['adventurer_id' => $user->id]);
    Quest::factory()->done()->create(['poster_id' => $user->id]);
    UserBadge::query()->create([
        'user_id' => $user->id,
        'badge' => Badge::FirstQuest,
        'earned_at' => now(),
    ]);
    $user = sharePublicProfile($user);

    $this->get(route('guild.member', ['publicId' => $user->public_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/member')
            ->where('member', function ($member): bool {
                $member = json_decode(json_encode($member), true);

                return array_keys($member) === [
                    'name',
                    'portrait_url',
                    'level',
                    'level_title',
                    'xp',
                    'quests_done',
                    'badges',
                    'joined_year',
                ] && $member['xp'] === 35
                    && $member['quests_done'] === 1
                    && $member['badges'] === [[
                        'key' => 'first_quest',
                        'label' => 'First Quest',
                        'description' => 'Finish 1 quest',
                    ]];
            }));
});

test('new users receive unique public ids and sharing is off', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    expect($first->public_id)
        ->toMatch('/^[0-9a-f]{24}$/')
        ->not->toBe($second->public_id)
        ->and($first->public_profile_enabled)->toBeFalse()
        ->and($second->public_profile_enabled)->toBeFalse();
});
