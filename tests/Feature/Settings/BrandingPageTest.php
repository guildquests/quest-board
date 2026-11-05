<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
});

function brandingPayload(array $overrides = []): array
{
    return [
        'app_name' => 'Guild Hall',
        'tagline' => 'Do the thing',
        'theme_preset' => 'stone-amber',
        ...$overrides,
    ];
}

test('an admin sees the branding page with every preset', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('branding.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/branding')
            ->has('presets', count(config('branding.presets')))
            ->where('presets.0.key', 'zinc-violet')
            ->where('uploaded', [
                'logo' => false,
                'banner' => false,
                'favicon' => false,
                'header' => false,
                'mascot_login' => false,
                'mascot_empty' => false,
                'mascot_reward' => false,
            ])
            ->where('builtIn', [
                'logo' => config('branding.assets.logo'),
                'banner' => config('branding.assets.banner'),
                'favicon' => null,
                'header' => config('branding.assets.header'),
                'mascot_login' => config('branding.assets.mascots.login'),
                'mascot_empty' => config('branding.assets.mascots.empty'),
                'mascot_reward' => config('branding.assets.mascots.reward'),
            ]));
});

test('the branding page reports uploaded images separately from built-in images', function () {
    Setting::current()->update([
        'logo_path' => 'branding/logo.webp',
        'mascot_reward_path' => 'branding/reward.webp',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('branding.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('uploaded.logo', true)
            ->where('uploaded.mascot_reward', true)
            ->where('uploaded.banner', false)
            ->where('uploaded.header', false)
            ->where('builtIn.header', config('branding.assets.header'))
            ->where('builtIn.favicon', null));
});

test('an admin can update the name, tagline and theme', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('branding.update'), brandingPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $setting = Setting::current();

    expect($setting->app_name)->toBe('Guild Hall')
        ->and($setting->tagline)->toBe('Do the thing')
        ->and($setting->theme_preset)->toBe('stone-amber');
});

test('members and leads cannot see or change the branding', function (string $state) {
    $user = $state === 'lead' ? User::factory()->lead()->create() : User::factory()->create();

    $this->actingAs($user)->get(route('branding.edit'))->assertForbidden();
    $this->actingAs($user)->post(route('branding.update'), brandingPayload())->assertForbidden();

    expect(Setting::current()->app_name)->not->toBe('Guild Hall');
})->with(['member' => 'member', 'lead' => 'lead']);

test('guests are sent to log in', function () {
    $this->get(route('branding.edit'))->assertRedirect(route('login'));
    $this->post(route('branding.update'), brandingPayload())->assertRedirect(route('login'));
});

test('uploads are stored on the public disk and replace the old file', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload([
        'logo' => UploadedFile::fake()->image('first.png', 64, 64),
        'banner' => UploadedFile::fake()->image('banner.jpg', 400, 100),
        'favicon' => UploadedFile::fake()->image('icon.webp', 32, 32),
        'header' => UploadedFile::fake()->image('header.webp', 800, 200),
        'mascot_login' => UploadedFile::fake()->image('login.webp', 200, 200),
        'mascot_empty' => UploadedFile::fake()->image('empty.webp', 200, 200),
        'mascot_reward' => UploadedFile::fake()->image('reward.webp', 200, 200),
    ]))->assertSessionHasNoErrors();

    // Copy the paths out: in tests the cache hands back the same model object each time.
    [$logo, $banner, $favicon, $header, $mascotLogin, $mascotEmpty, $mascotReward] = [
        Setting::current()->logo_path,
        Setting::current()->banner_path,
        Setting::current()->favicon_path,
        Setting::current()->header_path,
        Setting::current()->mascot_login_path,
        Setting::current()->mascot_empty_path,
        Setting::current()->mascot_reward_path,
    ];
    Storage::disk('public')->assertExists([$logo, $banner, $favicon, $header, $mascotLogin, $mascotEmpty, $mascotReward]);

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload([
        'logo' => UploadedFile::fake()->image('second.png', 96, 96),
        'header' => UploadedFile::fake()->image('second-header.webp', 1000, 250),
    ]))->assertSessionHasNoErrors();

    [$newLogo, $newHeader] = [Setting::current()->logo_path, Setting::current()->header_path];

    expect($newLogo)->not->toBe($logo)
        ->and($newHeader)->not->toBe($header)
        ->and(Setting::current()->banner_path)->toBe($banner);

    Storage::disk('public')->assertMissing([$logo, $header]);
    Storage::disk('public')->assertExists([$newLogo, $banner, $favicon, $newHeader, $mascotLogin, $mascotEmpty, $mascotReward]);

    $this->actingAs($admin)->get(route('board'))->assertInertia(fn (Assert $page) => $page
        ->where('branding.logo_url', Storage::disk('public')->url($newLogo))
        ->where('branding.header_url', Storage::disk('public')->url($newHeader))
        ->where('branding.mascots.login', Storage::disk('public')->url($mascotLogin))
        ->where('branding.mascots.empty', Storage::disk('public')->url($mascotEmpty))
        ->where('branding.mascots.reward', Storage::disk('public')->url($mascotReward)));
});

test('remove fields clear uploaded images and delete their files', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload([
        'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
        'mascot_empty' => UploadedFile::fake()->image('empty.webp', 200, 200),
    ]))->assertSessionHasNoErrors();

    [$logo, $mascotEmpty] = [Setting::current()->logo_path, Setting::current()->mascot_empty_path];
    Storage::disk('public')->assertExists([$logo, $mascotEmpty]);

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload([
        'remove_logo' => '1',
        'remove_mascot_empty' => '1',
    ]))->assertSessionHasNoErrors();

    expect(Setting::current()->logo_path)->toBeNull()
        ->and(Setting::current()->mascot_empty_path)->toBeNull();

    Storage::disk('public')->assertMissing([$logo, $mascotEmpty]);
});

test('a new upload wins when its remove field is also ticked', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload([
        'mascot_login' => UploadedFile::fake()->image('old-login.webp', 200, 200),
    ]))->assertSessionHasNoErrors();
    $old = Setting::current()->mascot_login_path;

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload([
        'mascot_login' => UploadedFile::fake()->image('new-login.webp', 240, 240),
        'remove_mascot_login' => '1',
    ]))->assertSessionHasNoErrors();
    $new = Setting::current()->mascot_login_path;

    expect($new)->not->toBe($old)
        ->and($new)->not->toBeNull();
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($new);
});

test('saving without a file keeps the existing images', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload([
        'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
    ]));
    $path = Setting::current()->logo_path;

    $this->actingAs($admin)->post(route('branding.update'), brandingPayload(['app_name' => 'Renamed']));

    expect(Setting::current()->logo_path)->toBe($path);
    Storage::disk('public')->assertExists($path);
});

test('svg uploads are rejected', function (string $field) {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('branding.update'), brandingPayload([
            $field => UploadedFile::fake()->createWithContent('logo.svg', $svg),
        ]))
        ->assertSessionHasErrors($field);

    expect(Setting::current()->{$field.'_path'})->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with(['logo', 'banner', 'favicon', 'header', 'mascot_login', 'mascot_empty', 'mascot_reward']);

// UploadedFile::fake() reports its type from the file name, so a real file is needed to test the content check.
test('an svg renamed to .png is still rejected', function () {
    $path = tempnam(sys_get_temp_dir(), 'brand');
    file_put_contents($path, '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    $file = new UploadedFile($path, 'logo.png', 'image/png', null, true);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('branding.update'), brandingPayload(['logo' => $file]))
        ->assertSessionHasErrors('logo');

    expect(Setting::current()->logo_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('files over the size limit are rejected', function (string $field, int $size) {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('branding.update'), brandingPayload([
            $field => UploadedFile::fake()->image('big.png')->size($size),
        ]))
        ->assertSessionHasErrors($field);
})->with([
    ['favicon', 600],
    ['header', 4097],
    ['mascot_empty', 2049],
]);

test('the name is required and the theme must be a known preset', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('branding.update'), brandingPayload(['app_name' => '', 'theme_preset' => 'neon']))
        ->assertSessionHasErrors(['app_name', 'theme_preset']);
});
