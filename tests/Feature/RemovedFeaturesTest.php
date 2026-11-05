<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests visiting the home page are redirected to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('signed-in users see the quest board on the home page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('quest-board'));
});

test('removed auth pages return 404', function (string $method, string $uri) {
    $this->call($method, $uri)->assertNotFound();
})->with([
    'register page' => ['GET', '/register'],
    'register submit' => ['POST', '/register'],
    'forgot password' => ['GET', '/forgot-password'],
    'reset password' => ['GET', '/reset-password/some-token'],
    'email verification' => ['GET', '/email/verify'],
    'two factor challenge' => ['GET', '/two-factor-challenge'],
    'passkey endpoints' => ['GET', '/.well-known/passkey-endpoints'],
    'passkey login options' => ['GET', '/passkeys/login/options'],
    'dashboard' => ['GET', '/dashboard'],
    'appearance settings' => ['GET', '/settings/appearance'],
]);
