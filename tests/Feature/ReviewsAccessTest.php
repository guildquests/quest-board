<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('leads and admins can open the reviews page', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)
        ->get(route('reviews'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('reviews'));
})->with(['admin', 'lead']);

test('members cannot open the reviews page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('reviews'))
        ->assertForbidden();
});

test('guests are redirected from the reviews page to login', function () {
    $this->get(route('reviews'))->assertRedirect(route('login'));
});
