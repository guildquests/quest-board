<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * User management is for admins only. An admin cannot change their own role or
 * access, so there is always at least one active admin.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function updateRole(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->isNot($target);
    }

    public function updateAccess(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->isNot($target);
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function impersonate(User $user, User $target): bool
    {
        return $user->isAdmin()
            && $user->isNot($target)
            && ! $target->isAdmin()
            && $target->isActive();
    }
}
