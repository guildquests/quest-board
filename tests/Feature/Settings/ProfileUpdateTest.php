<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile page receives the user email for the read-only field', function () {
    $user = User::factory()->create(['email' => 'adventurer@example.com']);

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->where('auth.user.email', 'adventurer@example.com'));
});

test('profile name can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->name)->toBe('Test User');
});

test('users cannot change their email address', function () {
    $user = User::factory()->create(['email' => 'original@example.com']);

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'changed@example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('original@example.com');
});

test('profile page receives the portraits and the current choice', function () {
    $user = User::factory()->withPortrait('portrait-04')->create();

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->has('portraits', 8)
            ->where('currentPortrait', 'portrait-04'));
});

test('users can pick their own portrait', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'portrait' => 'portrait-02',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->portrait)->toBe('portrait-02');
});

test('users can clear their portrait with an empty value', function () {
    $user = User::factory()->withPortrait('portrait-02')->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'portrait' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->portrait)->toBeNull();
});

test('an unknown portrait key is rejected', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'portrait' => 'portrait-99',
        ])
        ->assertSessionHasErrors('portrait')
        ->assertRedirect(route('profile.edit'));
});

test('a name-only update keeps the portrait', function () {
    $user = User::factory()->withPortrait('portrait-03')->create();

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'New Name'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->portrait)->toBe('portrait-03');
});

test('users cannot change their own role', function () {
    $user = User::factory()->create();
    $role = $user->role;

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'role' => 'admin',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->role)->toBe($role);
});

test('name is required', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name')
        ->assertRedirect(route('profile.edit'));
});

test('users cannot delete their account', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertMethodNotAllowed();

    expect($user->fresh())->not->toBeNull();
});
