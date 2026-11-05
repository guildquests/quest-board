<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

test('the login page renders the themed login component', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('the login page has no register or forgot-password links', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('/register')
        ->assertDontSee('/forgot-password');
});
