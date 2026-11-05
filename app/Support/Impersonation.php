<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class Impersonation
{
    public const SESSION_KEY = 'impersonator_id';

    public static function impersonatorId(Request $request): ?int
    {
        $id = $request->session()->get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    public static function isActive(Request $request): bool
    {
        return self::impersonatorId($request) !== null;
    }

    /**
     * Switch back to the admin and log a valid stop. Invalid admins lose the
     * session without calling logout, so the target is not signed out.
     */
    public static function restoreAdmin(Request $request): ?User
    {
        $adminId = self::impersonatorId($request);
        if ($adminId === null) {
            return null;
        }

        $target = $request->user();
        $admin = User::find($adminId);
        $request->session()->forget(self::SESSION_KEY);

        if (! $admin instanceof User || ! $admin->isAdmin() || ! $admin->isActive()) {
            self::invalidate($request);

            return null;
        }

        Auth::guard('web')->login($admin);
        Log::info('Impersonation stopped', [
            'admin_id' => $admin->id,
            'user_id' => $target instanceof User ? $target->id : null,
        ]);

        return $admin;
    }

    public static function invalidate(Request $request): void
    {
        Auth::guard('web')->forgetUser();
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
