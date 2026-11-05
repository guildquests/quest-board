<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

/**
 * Branding is for admins only.
 */
class SettingPolicy
{
    public function view(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }
}
