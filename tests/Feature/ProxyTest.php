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

test('a plain http request without proxy headers stays http', function () {
    $this->get('/_proxy-test')
        ->assertOk()
        ->assertJson(['secure' => false])
        ->assertJsonPath('url', fn (string $url) => str_starts_with($url, 'http://'));
});
