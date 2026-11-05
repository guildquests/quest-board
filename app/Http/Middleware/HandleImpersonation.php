<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Impersonation::isActive($request)) {
            return $next($request);
        }

        $admin = User::find(Impersonation::impersonatorId($request));
        if (! $admin instanceof User || ! $admin->isAdmin() || ! $admin->isActive()) {
            Impersonation::invalidate($request);

            return redirect()->route('login', status: 303);
        }

        $target = $request->user();
        if ($target instanceof User) {
            $target = $target->fresh();
        }
        if ($target instanceof User && (! $target->isActive() || $target->isAdmin())) {
            if (Impersonation::restoreAdmin($request) === null) {
                return redirect()->route('login', status: 303);
            }

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "{$target->name} can't be viewed as anymore.",
            ]);

            return redirect()->route('members.index', status: 303);
        }

        if ($request->routeIs('logout')) {
            if (Impersonation::restoreAdmin($request) === null) {
                return redirect()->route('login', status: 303);
            }

            return $next($request);
        }

        if (! in_array($request->method(), ['GET', 'HEAD'], true)
            && ! $request->routeIs('impersonation.destroy')) {
            Log::info('Impersonated action', [
                'admin_id' => $admin->id,
                'user_id' => $target instanceof User ? $target->id : null,
                'method' => $request->method(),
                'route' => $request->route()?->getName() ?? $request->path(),
            ]);
        }

        return $next($request);
    }
}
