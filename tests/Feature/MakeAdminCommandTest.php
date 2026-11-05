<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

test('quests:make-admin promotes an existing user', function () {
    $user = User::factory()->create(['name' => 'Yan']);

    $this->artisan('quests:make-admin', ['email' => $user->email])
        ->expectsOutput('Yan is now an admin.')
        ->assertSuccessful();

    expect($user->fresh()->role)->toBe(Role::Admin);
});

test('quests:make-admin ignores email case', function () {
    $user = User::factory()->create(['email' => 'yan@example.com']);

    $this->artisan('quests:make-admin', ['email' => 'Yan@Example.COM'])->assertSuccessful();

    expect($user->fresh()->role)->toBe(Role::Admin);
});

test('quests:make-admin fails for an unknown email', function () {
    $this->artisan('quests:make-admin', ['email' => 'nobody@example.com'])
        ->expectsOutput('No user with email nobody@example.com.')
        ->assertFailed();
});
