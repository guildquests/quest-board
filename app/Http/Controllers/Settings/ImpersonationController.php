<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_if(Impersonation::isActive($request), 403);

        Gate::authorize('impersonate', $user);

        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $request->session()->put(Impersonation::SESSION_KEY, $admin->id);
        Auth::guard('web')->login($user);
        Log::info('Impersonation started', [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
        ]);

        return to_route('board');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (! Impersonation::isActive($request)) {
            return to_route('board');
        }

        if (Impersonation::restoreAdmin($request) === null) {
            return redirect()->route('login', status: 303);
        }

        return to_route('members.index');
    }
}
