<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The install's single branding row. Read it with Setting::current().
 *
 * @property int $id
 * @property string $app_name
 * @property string|null $tagline
 * @property string|null $logo_path
 * @property string|null $banner_path
 * @property string|null $favicon_path
 * @property string|null $header_path
 * @property string|null $mascot_login_path
 * @property string|null $mascot_empty_path
 * @property string|null $mascot_reward_path
 * @property string $theme_preset
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'app_name',
    'tagline',
    'logo_path',
    'banner_path',
    'favicon_path',
    'header_path',
    'mascot_login_path',
    'mascot_empty_path',
    'mascot_reward_path',
    'theme_preset',
])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings.current';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * The current row, created from the config/branding.php defaults on first use.
     *
     * Only the attributes are cached: a serialized model comes back from a real cache store
     * (database, file) as an incomplete class.
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever(self::CACHE_KEY, fn (): array => self::query()->firstOrCreate([], [
            'app_name' => config('branding.defaults.name'),
            'tagline' => config('branding.defaults.tagline'),
            'theme_preset' => config('branding.defaults.theme'),
        ])->getAttributes());

        return (new self)->newFromBuilder($attributes);
    }

    /**
     * The active preset's colours, falling back to the default preset when the stored key is unknown.
     *
     * @return array<string, string>
     */
    public function colors(): array
    {
        $presets = config('branding.presets');

        return ($presets[$this->theme_preset] ?? $presets[config('branding.default_preset')])['colors'];
    }

    public function logoUrl(): string
    {
        return $this->uploadUrl($this->logo_path) ?? config('branding.assets.logo');
    }

    public function bannerUrl(): string
    {
        return $this->uploadUrl($this->banner_path) ?? config('branding.assets.banner');
    }

    public function headerUrl(): string
    {
        return $this->uploadUrl($this->header_path) ?? config('branding.assets.header');
    }

    /**
     * @return array{login: string, empty: string, reward: string}
     */
    public function mascotUrls(): array
    {
        return [
            'login' => $this->uploadUrl($this->mascot_login_path) ?? config('branding.assets.mascots.login'),
            'empty' => $this->uploadUrl($this->mascot_empty_path) ?? config('branding.assets.mascots.empty'),
            'reward' => $this->uploadUrl($this->mascot_reward_path) ?? config('branding.assets.mascots.reward'),
        ];
    }

    /** Null when no favicon was uploaded, so the layout uses the shipped icons. */
    public function faviconUrl(): ?string
    {
        return $this->uploadUrl($this->favicon_path);
    }

    private function uploadUrl(?string $path): ?string
    {
        return $path === null ? null : Storage::disk('public')->url($path);
    }
}
