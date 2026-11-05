<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('the shared demo prop is null when off and has the reset time when on', function () {
    $user = User::factory()->create();

    config(['demo.enabled' => false]);
    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where('demo', null));

    config(['demo.enabled' => true, 'demo.reset_time' => '03:30']);
    $this->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where('demo', ['reset_time' => '03:30']));
});

test('the login page only gets demo sign-in details in demo mode', function () {
    config(['demo.enabled' => false]);
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('demoAccounts', null)
            ->where('demoPassword', null));

    config(['demo.enabled' => true]);
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('demoAccounts', [
                ['email' => 'admin@example.com', 'role' => 'Admin'],
                ['email' => 'grace.marlow@example.com', 'role' => 'Lead'],
                ['email' => 'kit.thornton@example.com', 'role' => 'Member'],
            ])
            ->where('demoPassword', 'password'));
});

test('demo mode blocks password changes and admin password resets', function () {
    config(['demo.enabled' => true]);
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $originalPassword = $user->password;

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('password');

    $this->actingAs($admin)
        ->put(route('members.password', $user), ['password' => 'another-password'])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('demo mode blocks email changes and protects seeded account roles and access', function () {
    config(['demo.enabled' => true]);
    $admin = User::factory()->admin()->create();
    $seeded = User::factory()->create(['email' => 'kit.thornton@example.com']);

    $this->actingAs($admin)
        ->patch(route('members.update', $seeded), [
            'name' => $seeded->name,
            'email' => 'changed@example.com',
            'portrait' => $seeded->portrait,
        ])
        ->assertSessionHasErrors('email');

    $this->patch(route('members.role', $seeded), ['role' => 'lead'])
        ->assertSessionHasErrors('role');
    $this->patch(route('members.access', $seeded), ['active' => false])
        ->assertSessionHasErrors('active');

    $seeded->refresh();
    expect($seeded->email)->toBe('kit.thornton@example.com')
        ->and($seeded->role)->toBe(Role::Member)
        ->and($seeded->isActive())->toBeTrue();
});

test('demo mode still allows role and access changes for a non-seeded member', function () {
    config(['demo.enabled' => true]);
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['email' => 'visitor@example.com']);

    $this->actingAs($admin)
        ->patch(route('members.role', $member), ['role' => 'lead'])
        ->assertSessionHasNoErrors();
    $this->patch(route('members.access', $member), ['active' => false])
        ->assertSessionHasNoErrors();

    $member->refresh();
    expect($member->role)->toBe(Role::Lead)
        ->and($member->isActive())->toBeFalse();
});

test('all guarded changes work when demo mode is off', function () {
    config(['demo.enabled' => false]);
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['email' => 'kit.thornton@example.com']);

    $this->actingAs($member)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'member-new-password',
            'password_confirmation' => 'member-new-password',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->put(route('members.password', $member), ['password' => 'admin-new-password'])
        ->assertSessionHasNoErrors();
    $this->patch(route('members.update', $member), [
        'name' => $member->name,
        'email' => 'changed@example.com',
        'portrait' => $member->portrait,
    ])->assertSessionHasNoErrors();
    $this->patch(route('members.role', $member), ['role' => 'lead'])
        ->assertSessionHasNoErrors();
    $this->patch(route('members.access', $member), ['active' => false])
        ->assertSessionHasNoErrors();

    $member->refresh();
    expect(Hash::check('admin-new-password', $member->password))->toBeTrue()
        ->and($member->email)->toBe('changed@example.com')
        ->and($member->role)->toBe(Role::Lead)
        ->and($member->isActive())->toBeFalse();
});
