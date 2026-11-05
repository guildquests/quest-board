<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

dataset('shell routes', [
    'hall of fame' => ['hall-of-fame', 'hall-of-fame'],
    'my profile' => ['me', 'me'],
]);

test('signed-in users see each shell page', function (string $routeName, string $component) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with('shell routes');

test('guests are redirected from each shell page to login', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with('shell routes');
