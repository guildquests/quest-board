<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

test('new users default to the member role and are active', function () {
    $user = User::factory()->create()->fresh();

    expect($user->role)->toBe(Role::Member)
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->canReview())->toBeFalse()
        ->and($user->isActive())->toBeTrue();
});

test('role helpers follow the role', function () {
    $admin = User::factory()->admin()->create();
    $lead = User::factory()->lead()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->canReview())->toBeTrue()
        ->and($lead->isAdmin())->toBeFalse()
        ->and($lead->canReview())->toBeTrue();
});

test('deactivated users are not active', function () {
    $user = User::factory()->deactivated()->create();

    expect($user->isActive())->toBeFalse();
});

test('portrait url comes from config', function () {
    $user = User::factory()->withPortrait('portrait-03')->create();

    expect($user->portrait_url)->toBe('/assets/ui/portraits/portrait-03.webp');
});

test('portrait url is null without a portrait or with an unknown key', function () {
    $none = User::factory()->create();
    $unknown = User::factory()->withPortrait('portrait-99')->create();

    expect($none->portrait_url)->toBeNull()
        ->and($unknown->portrait_url)->toBeNull();
});

test('emails are stored lowercase', function () {
    $user = User::factory()->create(['email' => 'Yan.Linn@Example.COM']);

    expect($user->fresh()->email)->toBe('yan.linn@example.com');
});
