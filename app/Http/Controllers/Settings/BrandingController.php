<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    /** Upload field => settings column that holds its path. */
    private const UPLOADS = [
        'logo' => 'logo_path',
        'banner' => 'banner_path',
        'favicon' => 'favicon_path',
        'header' => 'header_path',
        'mascot_login' => 'mascot_login_path',
        'mascot_empty' => 'mascot_empty_path',
        'mascot_reward' => 'mascot_reward_path',
    ];

    /**
     * Show the branding form. The current values come from the shared `branding` prop.
     */
    public function edit(): Response
    {
        Gate::authorize('view', Setting::current());

        $presets = [];
        foreach (config('branding.presets') as $key => $preset) {
            $presets[] = ['key' => $key, 'label' => $preset['label'], 'colors' => $preset['colors']];
        }

        $setting = Setting::current();
        $uploaded = [];
        foreach (self::UPLOADS as $field => $column) {
            $uploaded[$field] = $setting->{$column} !== null;
        }

        $builtIn = [
            'logo' => config('branding.assets.logo'),
            'banner' => config('branding.assets.banner'),
            'favicon' => null,
            'header' => config('branding.assets.header'),
            'mascot_login' => config('branding.assets.mascots.login'),
            'mascot_empty' => config('branding.assets.mascots.empty'),
            'mascot_reward' => config('branding.assets.mascots.reward'),
        ];

        return Inertia::render('settings/branding', [
            'presets' => $presets,
            'uploaded' => $uploaded,
            'builtIn' => $builtIn,
        ]);
    }

    /**
     * Save the branding. A new upload replaces the old file, which is deleted.
     */
    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        $setting = Setting::current();
        $disk = Storage::disk('public');

        $data = $request->safe()->only(['app_name', 'tagline', 'theme_preset']);
        $replaced = [];

        foreach (self::UPLOADS as $field => $column) {
            if ($request->hasFile($field)) {
                $replaced[] = $setting->{$column};
                $data[$column] = $request->file($field)->store('branding', 'public');
            } elseif ($request->boolean("remove_{$field}") && $setting->{$column} !== null) {
                $replaced[] = $setting->{$column};
                $data[$column] = null;
            }
        }

        $setting->update($data);

        foreach (array_filter($replaced) as $old) {
            $disk->delete($old);
        }

        return back();
    }
}
