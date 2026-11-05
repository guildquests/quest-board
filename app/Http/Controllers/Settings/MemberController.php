<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Members\ResetMemberPasswordRequest;
use App\Http\Requests\Settings\Members\StoreMemberRequest;
use App\Http\Requests\Settings\Members\UpdateMemberAccessRequest;
use App\Http\Requests\Settings\Members\UpdateMemberRequest;
use App\Http\Requests\Settings\Members\UpdateMemberRoleRequest;
use App\Models\User;
use App\Services\QuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * List members, optionally filtered by role.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filter = Role::tryFrom((string) $request->query('role'));

        $members = User::query()
            ->when($filter, fn ($query, Role $role) => $query->where('role', $role->value))
            ->withProgress()
            ->orderBy('id')
            ->get();

        $counts = ['all' => User::count()];
        foreach (Role::cases() as $role) {
            $counts[$role->value] = User::where('role', $role->value)->count();
        }

        $portraits = [];
        foreach (config('quests.portraits') as $key => $url) {
            $portraits[] = ['key' => $key, 'url' => $url];
        }

        return Inertia::render('settings/members', [
            'members' => $members->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role->value,
                'portrait' => $member->portrait,
                'portrait_url' => $member->portrait_url,
                'active' => $member->isActive(),
                'is_self' => $member->is($request->user()),
                'can_impersonate' => $request->user()->can('impersonate', $member),
                'level' => $member->level()->number,
                'level_title' => $member->level()->title,
                'quests_done' => $member->questsDone(),
            ])->all(),
            'counts' => $counts,
            'filter' => $filter?->value,
            'roles' => array_map(fn (Role $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
            ], Role::cases()),
            'portraits' => $portraits,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'highlightId' => session('highlight'),
        ]);
    }

    /**
     * Add a member. The new user starts active.
     */
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return to_route('members.index')->with('highlight', $user->id);
    }

    /**
     * Edit a member's name, email, and portrait.
     */
    public function update(UpdateMemberRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return back();
    }

    public function updateRole(UpdateMemberRoleRequest $request, User $user): RedirectResponse
    {
        $user->role = Role::from($request->validated('role'));
        $user->save();

        return back();
    }

    /**
     * Deactivate or reactivate a member. deactivated_at is not mass assignable.
     */
    public function updateAccess(UpdateMemberAccessRequest $request, User $user, QuestService $quests): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $quests): void {
            $active = $request->boolean('active');

            $user->deactivated_at = $active ? null : now();
            $user->save();

            // A deactivated user can't finish their quest, so put it back on the board.
            if (! $active) {
                $quests->releaseFor($user);
            }
        });

        return back();
    }

    /**
     * Set a new password and end the member's sessions, so old logins and
     * "remember me" cookies stop working.
     */
    public function resetPassword(ResetMemberPasswordRequest $request, User $user): RedirectResponse
    {
        $user->password = $request->validated('password');
        $user->setRememberToken(Str::random(60));
        $user->save();

        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $user->id)
            ->when(
                $request->user()->is($user),
                fn ($query) => $query->where('id', '!=', Session::getId()),
            )
            ->delete();

        return back();
    }
}
