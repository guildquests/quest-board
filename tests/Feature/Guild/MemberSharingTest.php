<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected when changing sharing or regenerating a link', function () {
    $this->patch(route('me.sharing.update'), ['enabled' => true])
        ->assertRedirect(route('login'));

    $this->post(route('me.sharing.regenerate'))
        ->assertRedirect(route('login'));
});

test('members can turn sharing on and off', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('me.sharing.update'), ['enabled' => true])
        ->assertRedirect();

    expect($user->refresh()->public_profile_enabled)->toBeTrue();

    $this->actingAs($user)
        ->patch(route('me.sharing.update'), ['enabled' => false])
        ->assertRedirect();

    expect($user->refresh()->public_profile_enabled)->toBeFalse();
});

test('members can regenerate a link and the old link stops working', function () {
    $user = User::factory()->create();
    $user->forceFill(['public_profile_enabled' => true])->save();
    $oldPublicId = $user->public_id;

    $this->actingAs($user)
        ->post(route('me.sharing.regenerate'))
        ->assertRedirect();

    $newPublicId = $user->refresh()->public_id;

    expect($newPublicId)->not->toBe($oldPublicId);

    $this->get(route('guild.member', ['publicId' => $oldPublicId]))
        ->assertNotFound();

    $this->get(route('guild.member', ['publicId' => $newPublicId]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/member'));
});

test('the profile page receives the member card with sharing off', function () {
    $user = User::factory()->create(['name' => 'Card Member']);

    $this->actingAs($user)
        ->get(route('me'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('card.member.name', 'Card Member')
            ->where('card.public_url', route('guild.member', ['publicId' => $user->public_id]))
            ->where('card.qr', null)
            ->where('card.sharing_enabled', false));
});

test('the profile page receives a qr when sharing is on', function () {
    $user = User::factory()->create();
    $user->forceFill(['public_profile_enabled' => true])->save();

    $this->actingAs($user)
        ->get(route('me'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('card.public_url', route('guild.member', ['publicId' => $user->public_id]))
            ->where('card.qr', fn (?string $qr): bool => $qr !== null && str_starts_with($qr, 'data:image/svg+xml;base64,'))
            ->where('card.sharing_enabled', true));
});
