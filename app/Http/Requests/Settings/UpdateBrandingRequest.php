<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::current()) ?? false;
    }

    /**
     * Images are PNG, JPG or WebP only. SVG is refused because it can carry scripts;
     * `mimes` checks the file's content, so renaming an SVG does not get it through.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'theme_preset' => ['required', Rule::in(array_keys(config('branding.presets')))],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'banner' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:512'],
            'header' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'mascot_login' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'mascot_empty' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'mascot_reward' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_banner' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'remove_header' => ['nullable', 'boolean'],
            'remove_mascot_login' => ['nullable', 'boolean'],
            'remove_mascot_empty' => ['nullable', 'boolean'],
            'remove_mascot_reward' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.mimes' => 'Use a PNG, JPG or WebP image.',
            'banner.mimes' => 'Use a PNG, JPG or WebP image.',
            'favicon.mimes' => 'Use a PNG, JPG or WebP image.',
            'header.mimes' => 'Use a PNG, JPG or WebP image.',
            'mascot_login.mimes' => 'Use a PNG, JPG or WebP image.',
            'mascot_empty.mimes' => 'Use a PNG, JPG or WebP image.',
            'mascot_reward.mimes' => 'Use a PNG, JPG or WebP image.',
            'logo.max' => 'The logo can be up to 2 MB.',
            'banner.max' => 'The banner can be up to 4 MB.',
            'favicon.max' => 'The favicon can be up to 512 KB.',
            'header.max' => 'The header image can be up to 4 MB.',
            'mascot_login.max' => 'The login mascot can be up to 2 MB.',
            'mascot_empty.max' => 'The empty state mascot can be up to 2 MB.',
            'mascot_reward.max' => 'The reward mascot can be up to 2 MB.',
        ];
    }
}
