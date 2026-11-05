<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PublicProfile;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PublicProfileController extends Controller
{
    public function __invoke(Request $request, string $publicId): InertiaResponse|SymfonyResponse
    {
        $user = User::query()->where('public_id', $publicId)->first();

        if ($user === null || ! $user->public_profile_enabled || ! $user->isActive()) {
            return Inertia::render('public/member-unavailable')
                ->toResponse($request)
                ->setStatusCode(404);
        }

        return Inertia::render('public/member', [
            'member' => PublicProfile::for($user)->toArray(),
            'qr' => QrCode::svgDataUri(route('guild.member', ['publicId' => $user->public_id])),
        ]);
    }
}
