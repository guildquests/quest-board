<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

afterEach(function (): void {
    $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    RefreshDatabaseState::$migrated = false;
});

test('the demo reset refuses to run when demo mode is off', function () {
    config(['demo.enabled' => false]);
    Storage::fake('public');
    Storage::disk('public')->put('branding/logo.png', 'logo');
    $user = User::factory()->create();

    $this->artisan('quests:demo-reset')
        ->expectsOutputToContain('Demo mode is off')
        ->assertFailed();

    $this->assertDatabaseHas('users', ['id' => $user->id]);
    Storage::disk('public')->assertExists('branding/logo.png');
    expect(app()->isDownForMaintenance())->toBeFalse();
});

test('the demo reset wipes data seeds demo accounts and deletes uploads', function () {
    config(['demo.enabled' => true]);
    Storage::fake('public');
    Storage::disk('public')->put('branding/logo.png', 'logo');
    Storage::disk('public')->put('.gitignore', '');
    User::factory()->create(['email' => 'stray@example.com']);
    Log::spy();

    $this->artisan('quests:demo-reset')
        ->expectsOutput('Demo reset finished.')
        ->assertSuccessful();

    $this->assertDatabaseMissing('users', ['email' => 'stray@example.com']);
    $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    Storage::disk('public')->assertMissing('branding/logo.png');
    Storage::disk('public')->assertExists('.gitignore');
    expect(app()->isDownForMaintenance())->toBeFalse();
    Log::shouldHaveReceived('info', ['Demo reset started.'])->once();
    Log::shouldHaveReceived('info', ['Demo reset finished.'])->once();
});

test('the demo reset lifts maintenance mode when a step fails', function () {
    config(['demo.enabled' => true]);
    Log::spy();
    Storage::shouldReceive('disk')
        ->once()
        ->with('public')
        ->andThrow(new RuntimeException('Public disk failed.'));

    $this->artisan('quests:demo-reset')
        ->expectsOutputToContain('Demo reset failed: Public disk failed.')
        ->assertFailed();

    expect(app()->isDownForMaintenance())->toBeFalse();
    Log::shouldHaveReceived('error', ['Demo reset failed: Public disk failed.'])->once();
});

test('the demo reset is not scheduled when demo mode is off', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'quests:demo-reset'));

    expect($events)->toHaveCount(0);
});

test('the demo reset is scheduled at the configured time when demo mode is on', function () {
    config([
        'demo.enabled' => true,
        'demo.reset_time' => '03:30',
    ]);

    require base_path('routes/console.php');

    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'quests:demo-reset'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('30 3 * * *');
});
