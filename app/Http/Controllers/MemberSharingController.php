<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMemberSharingRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberSharingController extends Controller
{
    public function update(UpdateMemberSharingRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $enabled = (bool) $request->validated('enabled');

        $user->forceFill(['public_profile_enabled' => $enabled])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $enabled
                ? 'Your profile is now shared.'
                : 'Your profile is now private.',
        ]);

        return back();
    }

    public function regenerate(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->regeneratePublicId();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Your profile link was replaced.',
        ]);

        return back();
    }
}
