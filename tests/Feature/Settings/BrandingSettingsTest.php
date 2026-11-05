<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

test('the first read creates the single row from the brand defaults', function () {
    config([
        'branding.defaults.name' => 'Guild Hall',
        'branding.defaults.tagline' => 'Do the thing',
        'branding.defaults.theme' => 'stone-amber',
    ]);

    expect(Setting::query()->count())->toBe(0);

    $setting = Setting::current();

    expect(Setting::query()->count())->toBe(1)
        ->and($setting->app_name)->toBe('Guild Hall')
        ->and($setting->tagline)->toBe('Do the thing')
        ->and($setting->theme_preset)->toBe('stone-amber');

    Setting::current();

    expect(Setting::query()->count())->toBe(1);
});

test('the default preset is neutral, not the accent and primary team look', function () {
    expect(config('branding.defaults.theme'))->toBe('zinc-violet')
        ->and(config('branding.default_preset'))->toBe('zinc-violet')
        ->and(Setting::current()->theme_preset)->not->toBe('accent-primary');
});

test('saving a setting clears the cached row', function () {
    $setting = Setting::current();

    expect(Cache::has('settings.current'))->toBeTrue();

    $setting->update(['app_name' => 'Renamed']);

    expect(Cache::has('settings.current'))->toBeFalse()
        ->and(Setting::current()->app_name)->toBe('Renamed');
});

// The array cache used by the other tests hands back the same object, which hides serialization problems.
test('the row survives a cache store that serializes, like the database store', function () {
    config(['cache.default' => 'database']);
    Cache::purge();

    Setting::current()->update(['app_name' => 'Guild Hall']);

    $setting = Setting::current();
    expect(Cache::get('settings.current'))->toBeArray();

    $again = Setting::current();

    expect($again)->toBeInstanceOf(Setting::class)
        ->and($again->exists)->toBeTrue()
        ->and($again->app_name)->toBe('Guild Hall')
        ->and($again->logoUrl())->toBe('/assets/brand/logo.webp')
        ->and($setting->is($again))->toBeTrue();

    $again->update(['app_name' => 'Renamed']);

    expect(Setting::current()->app_name)->toBe('Renamed');
});

test('every preset defines the same colour tokens', function () {
    $tokens = array_keys(config('branding.presets.accent-primary.colors'));

    foreach (config('branding.presets') as $key => $preset) {
        expect(array_keys($preset['colors']))->toEqualCanonicalizing($tokens, "preset {$key}");
    }
});

test('an unknown preset falls back to the default colours', function () {
    $setting = Setting::current();
    $setting->update(['theme_preset' => 'no-such-preset']);

    expect($setting->colors())->toBe(config('branding.presets.zinc-violet.colors'));
});

test('the shared props contain the branding', function () {
    Setting::current()->update(['app_name' => 'Guild Hall', 'tagline' => 'Do the thing', 'theme_preset' => 'slate-indigo']);

    $this->actingAs(User::factory()->create())
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('branding', [
                'name' => 'Guild Hall',
                'tagline' => 'Do the thing',
                'logo_url' => '/assets/brand/logo.webp',
                'banner_url' => '/assets/brand/banner.webp',
                'favicon_url' => null,
                'header_url' => '/assets/brand/header.webp',
                'mascots' => [
                    'login' => '/assets/brand/mascot-login.webp',
                    'empty' => '/assets/brand/mascot-empty.webp',
                    'reward' => '/assets/brand/mascot-reward.webp',
                ],
                'theme_preset' => 'slate-indigo',
            ]));
});

test('the shared branding tagline falls back to the default when it is null', function () {
    Setting::current()->update(['tagline' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where(
            'branding.tagline',
            config('branding.defaults.tagline'),
        ));
});

test('the page head carries the name and the active preset colours', function () {
    Setting::current()->update(['app_name' => 'Guild Hall', 'theme_preset' => 'stone-amber']);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="application-name" content="Guild Hall">', false)
        ->assertSee('--color-primary: #f59e0b;', false);
});
