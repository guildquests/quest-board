<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;

test('an admin can impersonate an active member or lead', function (Role $role) {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->state(['role' => $role])->create();

    $this->actingAs($admin)
        ->post(route('members.impersonate', $target))
        ->assertRedirect(route('board'));

    $this->assertAuthenticatedAs($target);
    expect(session('impersonator_id'))->toBe($admin->id);
})->with([Role::Member, Role::Lead]);

test('non-admins cannot start impersonation', function () {
    $actor = User::factory()->lead()->create();
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->post(route('members.impersonate', $target))
        ->assertForbidden();
});

test('admins cannot impersonate themselves, another admin, or a deactivated user', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $deactivated = User::factory()->deactivated()->create();

    $this->actingAs($admin);

    $this->post(route('members.impersonate', $admin))->assertForbidden();
    $this->post(route('members.impersonate', $otherAdmin))->assertForbidden();
    $this->post(route('members.impersonate', $deactivated))->assertForbidden();

    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});

test('starting while already impersonating is forbidden', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $otherTarget = User::factory()->lead()->create();

    $this->actingAs($target)
        ->withSession(['impersonator_id' => $admin->id])
        ->post(route('members.impersonate', $otherTarget))
        ->assertForbidden();
});

test('actions made while impersonating are recorded for the target user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $quest = Quest::factory()->create(['poster_id' => $admin->id]);

    $this->actingAs($admin)->post(route('members.impersonate', $target));
    $this->post(route('quests.accept', $quest))->assertRedirect();

    expect($quest->fresh()->adventurer_id)->toBe($target->id);
});

test('impersonated users cannot view admin-only pages', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('members.impersonate', $target));

    $this->get(route('members.index'))->assertForbidden();
});

test('stopping impersonation restores the admin and redirects to members', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('members.impersonate', $target));

    $this->delete(route('impersonation.destroy'))
        ->assertRedirect(route('members.index'));

    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});

test('logging out while impersonating leaves the target remember token unchanged', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['remember_token' => 'target-token']);

    $this->actingAs($admin)->post(route('members.impersonate', $target));
    $this->post(route('logout'))->assertRedirect(route('board'));

    $this->assertGuest();
    expect($target->fresh()->remember_token)->toBe('target-token');
});

test('a target deactivated during impersonation is returned to the admin', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('members.impersonate', $target));
    $target->forceFill(['deactivated_at' => now()])->save();

    $this->get(route('board'))
        ->assertStatus(303)
        ->assertRedirect(route('members.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "{$target->name} can't be viewed as anymore.",
        ]);

    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});

test('an impersonator demoted during impersonation loses the session', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('members.impersonate', $target));
    $admin->forceFill(['role' => Role::Member])->save();

    $this->get(route('board'))
        ->assertStatus(303)
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the shared impersonation prop is null normally and names the admin while active', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Player']);
    $target = User::factory()->create();

    $this->actingAs($target)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where('impersonation', null));

    $this->actingAs($admin)->post(route('members.impersonate', $target));

    $this->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('impersonation', ['name' => 'Admin Player']));
});

test('the board does not expose rewards while impersonating', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    Reward::factory()->create(['user_id' => $target->id]);

    $this->actingAs($admin)->post(route('members.impersonate', $target));

    $this->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where('rewards', []));
});

test('the members page reports which users can be impersonated', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $lead = User::factory()->lead()->create();
    $otherAdmin = User::factory()->admin()->create();
    $deactivated = User::factory()->deactivated()->create();

    $this->actingAs($admin)
        ->get(route('members.index'))
        ->assertInertia(fn (Assert $page) => $page->where(
            'members',
            function (Collection $members) use ($admin, $member, $lead, $otherAdmin, $deactivated): bool {
                $canImpersonate = [];
                foreach ($members as $row) {
                    $canImpersonate[(int) $row['id']] = $row['can_impersonate'];
                }

                return $canImpersonate === [
                    $admin->id => false,
                    $member->id => true,
                    $lead->id => true,
                    $otherAdmin->id => false,
                    $deactivated->id => false,
                ];
            },
        ));
});

test('start, stop, and impersonated actions are logged', function () {
    Log::spy();

    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $quest = Quest::factory()->create(['poster_id' => $admin->id]);

    $this->actingAs($admin)->post(route('members.impersonate', $target));
    Log::shouldHaveReceived('info')->with('Impersonation started', [
        'admin_id' => $admin->id,
        'user_id' => $target->id,
    ]);

    $this->post(route('quests.accept', $quest));
    Log::shouldHaveReceived('info')->with('Impersonated action', [
        'admin_id' => $admin->id,
        'user_id' => $target->id,
        'method' => 'POST',
        'route' => 'quests.accept',
    ]);

    $this->delete(route('impersonation.destroy'));
    Log::shouldHaveReceived('info')->with('Impersonation stopped', [
        'admin_id' => $admin->id,
        'user_id' => $target->id,
    ]);
});
