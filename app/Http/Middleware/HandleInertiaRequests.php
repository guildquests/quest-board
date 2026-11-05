<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\Setting;
use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'branding' => fn (): array => [
                'name' => Setting::current()->app_name,
                'tagline' => Setting::current()->tagline ?? config('branding.defaults.tagline'),
                'logo_url' => Setting::current()->logoUrl(),
                'banner_url' => Setting::current()->bannerUrl(),
                'favicon_url' => Setting::current()->faviconUrl(),
                'header_url' => Setting::current()->headerUrl(),
                'mascots' => Setting::current()->mascotUrls(),
                'theme_preset' => Setting::current()->theme_preset,
            ],
            'repositoryUrl' => config('app.repository_url') ?: null,
            'reviewCount' => fn (): ?int => $request->user()?->canReview()
                ? Quest::query()->status(QuestStatus::InReview)->count()
                : null,
            'impersonation' => function () use ($request): ?array {
                $adminId = Impersonation::impersonatorId($request);
                $admin = $adminId === null ? null : User::find($adminId);

                return $admin === null ? null : ['name' => $admin->name];
            },
            'demo' => config('demo.enabled') ? [
                'reset_time' => config('demo.reset_time'),
            ] : null,
            'auth' => [
                'user' => $request->user() === null ? null : [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                    'portrait_url' => $request->user()->portrait_url,
                    'can_review' => $request->user()->canReview(),
                ],
            ],
            'progress' => $request->user() === null ? null : [
                'xp' => $request->user()->loadProgress()->lifetimeXp(),
                ...$request->user()->level()->toArray(),
            ],
        ];
    }
}
