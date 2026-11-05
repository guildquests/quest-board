<?php

declare(strict_types=1);

use App\Models\User;

test('a deactivated user cannot log in and sees the same error as wrong credentials', function () {
    $user = User::factory()->deactivated()->create();

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => "Those details don't match an account."]);

    $this->assertGuest();

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => "Those details don't match an account."]);
});

test('an active user still logs in', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('login ignores email case', function () {
    $user = User::factory()->create(['email' => 'yan@example.com']);

    $this->post(route('login.store'), [
        'email' => 'Yan@Example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('a user deactivated while logged in is signed out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('board'))->assertOk();

    // deactivated_at is not mass assignable, so update() would silently skip it.
    $user->forceFill(['deactivated_at' => now()])->save();

    $this->get(route('board'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a deactivated user is redirected with a 303 on a PATCH, so the browser follows with GET', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // deactivated_at is not mass assignable, so update() would silently skip it.
    $user->forceFill(['deactivated_at' => now()])->save();

    $this->patch(route('profile.update'), ['name' => 'New Name'])
        ->assertStatus(303)
        ->assertRedirect(route('login'));

    $this->assertGuest();

    expect($user->fresh()->name)->not->toBe('New Name');
});
