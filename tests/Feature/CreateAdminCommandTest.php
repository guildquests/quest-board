<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('quests:create-admin creates an admin from options', function () {
    $this->artisan('quests:create-admin', [
        '--name' => 'Ada Lovelace',
        '--email' => 'Ada@Example.com',
        '--password' => 'correct-horse-battery',
        '--no-interaction' => true,
    ])
        ->expectsOutput('Admin ada@example.com is ready. They can log in now.')
        ->assertSuccessful();

    $user = User::where('email', 'ada@example.com')->firstOrFail();

    expect($user->name)->toBe('Ada Lovelace')
        ->and($user->role)->toBe(Role::Admin)
        ->and($user->isAdmin())->toBeTrue()
        ->and(Hash::check('correct-horse-battery', $user->password))->toBeTrue();
});

test('quests:create-admin asks for what is missing and confirms the password', function () {
    $this->artisan('quests:create-admin')
        ->expectsQuestion('Name', 'Grace Hopper')
        ->expectsQuestion('Email', 'grace@example.com')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->expectsQuestion('Confirm password', 'correct-horse-battery')
        ->assertSuccessful();

    expect(User::where('email', 'grace@example.com')->firstOrFail()->role)->toBe(Role::Admin);
});

test('quests:create-admin only asks for the options it was not given', function () {
    $this->artisan('quests:create-admin', ['--name' => 'Grace Hopper', '--email' => 'grace@example.com'])
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->expectsQuestion('Confirm password', 'correct-horse-battery')
        ->assertSuccessful();

    expect(User::where('email', 'grace@example.com')->exists())->toBeTrue();
});

test('quests:create-admin refuses an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('quests:create-admin', [
        '--name' => 'Someone',
        '--email' => 'TAKEN@example.com',
        '--password' => 'correct-horse-battery',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('The email has already been taken.')
        ->assertFailed();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1)
        ->and(User::where('email', 'taken@example.com')->first()->role)->toBe(Role::Member);
});

test('quests:create-admin rejects invalid input and creates nothing', function (array $options, string $message) {
    $this->artisan('quests:create-admin', [
        '--name' => 'Ada Lovelace',
        '--email' => 'ada@example.com',
        '--password' => 'correct-horse-battery',
        '--no-interaction' => true,
        ...$options,
    ])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(User::count())->toBe(0);
})->with([
    'bad email' => [['--email' => 'not-an-email'], 'The email field must be a valid email address.'],
    'short password' => [['--password' => 'short'], 'The password field must be at least 8 characters.'],
    'blank name' => [['--name' => ' '], 'The name field is required.'],
]);

test('quests:create-admin fails without prompting when options are missing and it is not interactive', function () {
    $this->artisan('quests:create-admin', ['--no-interaction' => true])
        ->expectsOutputToContain('The name field is required.')
        ->expectsOutputToContain('The email field is required.')
        ->expectsOutputToContain('The password field is required.')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('quests:create-admin applies the strong production password rules', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('quests:create-admin', [
        '--name' => 'Ada Lovelace',
        '--email' => 'ada@example.com',
        '--password' => 'alllowercase',
        '--no-interaction' => true,
    ])->assertFailed();

    expect(User::count())->toBe(0);
});
