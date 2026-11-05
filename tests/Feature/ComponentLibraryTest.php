<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the component library to login', function () {
    $this->get(route('docs.library'))->assertRedirect(route('login'));
});

test('signed-in users see the component library', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('docs.library'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('docs/library')
            ->where('sample_qr', fn (string $qr): bool => str_starts_with(
                $qr,
                'data:image/svg+xml;base64,',
            )));
});
