<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RewardSeenRequest;
use App\Models\User;
use App\Support\Level;
use App\Support\RewardQueue;
use Illuminate\Http\RedirectResponse;

class RewardController extends Controller
{
    public function seen(RewardSeenRequest $request): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = $request->validated('ids');
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $oldest = $user->rewards()
            ->whereNull('seen_at')
            ->oldest('created_at')
            ->oldest('id')
            ->first();

        $user->rewards()
            ->whereNull('seen_at')
            ->whereKey($ids)
            ->update(['seen_at' => now()]);

        $key = RewardQueue::startLevelKey($user);

        if ($user->rewards()->whereNull('seen_at')->exists()) {
            if ($oldest !== null && ! $request->session()->has($key)) {
                $request->session()->put($key, Level::fromXp((int) $oldest->xp_before)->number);
            }
        } else {
            $request->session()->forget($key);
        }

        return back();
    }
}
