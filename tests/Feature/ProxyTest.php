<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_proxy-test', fn () => response()->json([
        'url' => route('login'),
        'secure' => request()->isSecure(),
    ]));
});

test('a request forwarded as https produces https route URLs', function () {
    $this->get('/_proxy-test', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'quests.example.com',
        'X-Forwarded-Port' => '443',
    ])
        ->assertOk()
        ->assertJson([
            'url' => 'https://quests.example.com/login',
            'secure' => true,
        ]);
});

test('in production with an https APP_URL, a request forwarded as http is treated as https', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.url' => 'https://quests.example.com']);

    $this->get('/_proxy-test', [
        'X-Forwarded-Proto' => 'http',
        'X-Forwarded-Host' => 'quests.example.com',
        'X-Forwarded-Port' => '80',
    ])
        ->assertOk()
        ->assertJson([
            'url' => 'https://quests.example.com/login',
            'secure' => true,
        ]);
});

test('the saved login link is https when the proxy forwards http in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.url' => 'https://quests.example.com']);

    $this->get('/', [
        'X-Forwarded-Proto' => 'http',
        'X-Forwarded-Host' => 'quests.example.com',
        'X-Forwarded-Port' => '80',
    ])->assertRedirect();

    expect(session('url.intended'))->toBe('https://quests.example.com');
});

test('a plain http request without proxy headers stays http', function () {
    $this->get('/_proxy-test')
        ->assertOk()
        ->assertJson(['secure' => false])
        ->assertJsonPath('url', fn (string $url) => str_starts_with($url, 'http://'));
});
