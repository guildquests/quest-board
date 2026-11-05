<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Enums\Role;
use App\Models\Quest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Inertia\Testing\AssertableInertia as Assert;

function validMember(array $overrides = []): array
{
    return [
        'name' => 'New Person',
        'email' => 'new.person@example.com',
        'role' => 'member',
        'portrait' => 'portrait-04',
        'password' => 'a-fine-password',
        ...$overrides,
    ];
}

describe('access', function () {
    test('admins can view the members page', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/members')
                ->has('members', 1)
                ->has('roles', 3)
                ->has('portraits', 8)
                ->has('passwordRules'));
    });

    test('leads and members get 403 on every members route', function (Role $role) {
        $actor = User::factory()->create(['role' => $role]);
        $target = User::factory()->create();

        $this->actingAs($actor);

        $this->get(route('members.index'))->assertForbidden();
        $this->post(route('members.store'), validMember())->assertForbidden();
        $this->patch(route('members.update', $target), ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
        $this->patch(route('members.role', $target), ['role' => 'lead'])->assertForbidden();
        $this->patch(route('members.access', $target), ['active' => false])->assertForbidden();
        $this->put(route('members.password', $target), ['password' => 'a-fine-password'])->assertForbidden();

        expect(User::count())->toBe(2);
    })->with([Role::Lead, Role::Member]);

    test('guests are redirected to login', function () {
        $this->get(route('members.index'))->assertRedirect(route('login'));
    });
});

describe('listing', function () {
    test('counts include deactivated users and the filter narrows the list', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->lead()->create();
        User::factory()->count(2)->create();
        User::factory()->deactivated()->create();

        $this->actingAs($admin)
            ->get(route('members.index', ['role' => 'member']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts', ['all' => 5, 'member' => 3, 'lead' => 1, 'admin' => 1])
                ->where('filter', 'member')
                ->has('members', 3));
    });

    test('an unknown role filter shows everyone', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create();

        $this->actingAs($admin)
            ->get(route('members.index', ['role' => 'wizard']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filter', null)
                ->has('members', 2));
    });

    test('rows carry what the page needs and mark the signed-in admin', function () {
        $admin = User::factory()->admin()->withPortrait('portrait-02')->create();

        $this->actingAs($admin)
            ->get(route('members.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('members.0.id', $admin->id)
                ->where('members.0.role', 'admin')
                ->where('members.0.portrait', 'portrait-02')
                ->where('members.0.portrait_url', '/assets/ui/portraits/portrait-02.webp')
                ->where('members.0.active', true)
                ->where('members.0.is_self', true)
                ->where('members.0.level', 1)
                ->where('members.0.level_title', 'Apprentice')
                ->where('members.0.quests_done', 0));
    });
});

describe('adding', function () {
    test('an admin can add a member who starts active', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('members.store'), validMember(['role' => 'lead']));

        $user = User::where('email', 'new.person@example.com')->firstOrFail();

        $response->assertRedirect(route('members.index'))->assertSessionHas('highlight', $user->id);
        expect($user->role)->toBe(Role::Lead)
            ->and($user->portrait)->toBe('portrait-04')
            ->and($user->isActive())->toBeTrue()
            ->and(password_verify('a-fine-password', $user->password))->toBeTrue();
    });

    test('a portrait is optional', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('members.store'), validMember(['portrait' => null]))
            ->assertSessionHasNoErrors();

        expect(User::where('email', 'new.person@example.com')->firstOrFail()->portrait)->toBeNull();
    });

    test('validation rejects bad input', function (array $overrides, string $field) {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('members.store'), validMember($overrides))
            ->assertSessionHasErrors($field);
    })->with([
        'email taken, different case' => [['email' => 'Taken@Example.com'], 'email'],
        'unknown portrait key' => [['portrait' => 'portrait-99'], 'portrait'],
        'invalid role' => [['role' => 'wizard'], 'role'],
        'missing role' => [['role' => ''], 'role'],
        'short password' => [['password' => 'short'], 'password'],
        'missing name' => [['name' => ''], 'name'],
    ]);

    test('the email is stored lowercase', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('members.store'), validMember(['email' => 'Mixed.Case@Example.com']));

        expect(User::where('email', 'mixed.case@example.com')->exists())->toBeTrue();
    });
});

describe('editing', function () {
    test('an admin can edit name, email, and portrait', function () {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.update', $target), [
                'name' => 'Renamed',
                'email' => 'Renamed@Example.com',
                'portrait' => 'portrait-07',
            ])
            ->assertSessionHasNoErrors();

        $target->refresh();
        expect($target->name)->toBe('Renamed')
            ->and($target->email)->toBe('renamed@example.com')
            ->and($target->portrait)->toBe('portrait-07');
    });

    test('a portrait can be cleared', function () {
        $target = User::factory()->withPortrait()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'portrait' => null,
            ]);

        expect($target->fresh()->portrait)->toBeNull();
    });

    test('a user can keep their own email but not take another one', function () {
        $target = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('members.update', $target), ['name' => 'Same Email', 'email' => $target->email])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('members.update', $target), ['name' => 'Taken', 'email' => strtoupper($other->email)])
            ->assertSessionHasErrors('email');
    });

    test('an unknown portrait key is rejected', function () {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'portrait' => 'portrait-99',
            ])
            ->assertSessionHasErrors('portrait');
    });

    test('editing does not change the role', function () {
        $target = User::factory()->lead()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.update', $target), [
                'name' => 'Still Lead',
                'email' => $target->email,
                'role' => 'admin',
            ]);

        expect($target->fresh()->role)->toBe(Role::Lead);
    });
});

describe('role', function () {
    test('an admin can change another user\'s role', function () {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.role', $target), ['role' => 'lead'])
            ->assertSessionHasNoErrors();

        expect($target->fresh()->role)->toBe(Role::Lead);
    });

    test('an invalid role is rejected', function () {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.role', $target), ['role' => 'wizard'])
            ->assertSessionHasErrors('role');
    });

    test('an admin cannot change their own role', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('members.role', $admin), ['role' => 'member'])
            ->assertForbidden();

        expect($admin->fresh()->role)->toBe(Role::Admin);
    });
});

describe('access', function () {
    test('an admin can deactivate and reactivate a user', function () {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->patch(route('members.access', $target), ['active' => false]);
        expect($target->fresh()->isActive())->toBeFalse();

        $this->actingAs($admin)->patch(route('members.access', $target), ['active' => true]);
        expect($target->fresh()->isActive())->toBeTrue();
    });

    test('deactivating a user releases their active quest', function (string $state) {
        $quest = Quest::factory()->{$state}()->create();
        $other = Quest::factory()->inProgress()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.access', $quest->adventurer), ['active' => false]);

        $event = $quest->events()->firstOrFail();

        expect($quest->fresh()->status)->toBe(QuestStatus::Open)
            ->and($quest->fresh()->adventurer_id)->toBeNull()
            ->and($event->event)->toBe(ActivityEvent::Released)
            ->and($event->user_id)->toBeNull()
            ->and($other->fresh()->status)->toBe(QuestStatus::InProgress);
    })->with(['in progress' => 'inProgress', 'in review' => 'inReview']);

    test('deactivating keeps the quests they posted and finished', function () {
        $poster = User::factory()->create();
        $open = Quest::factory()->create(['poster_id' => $poster->id]);
        $done = Quest::factory()->done()->create();

        $this->actingAs(User::factory()->admin()->create());
        $this->patch(route('members.access', $poster), ['active' => false]);
        $this->patch(route('members.access', $done->adventurer), ['active' => false]);

        expect($open->fresh()->status)->toBe(QuestStatus::Open)
            ->and($done->fresh()->status)->toBe(QuestStatus::Done)
            ->and($done->fresh()->adventurer_id)->toBe($done->adventurer_id);
    });

    test('reactivating a user does not change quests', function () {
        $quest = Quest::factory()->inProgress()->create();
        $quest->adventurer->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.access', $quest->adventurer), ['active' => true]);

        expect($quest->fresh()->status)->toBe(QuestStatus::InProgress)
            ->and($quest->events()->count())->toBe(0);
    });

    test('an admin cannot deactivate themselves', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('members.access', $admin), ['active' => false])
            ->assertForbidden();

        expect($admin->fresh()->isActive())->toBeTrue();
    });

    test('the active flag is required', function () {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('members.access', $target), [])
            ->assertSessionHasErrors('active');
    });
});

describe('password reset', function () {
    test('changes the password, cycles the remember token, and ends the user\'s sessions', function () {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['remember_token' => 'old-token']);
        $other = User::factory()->create();

        foreach ([['s-target-1', $target->id], ['s-target-2', $target->id], ['s-other', $other->id]] as [$id, $userId]) {
            DB::table('sessions')->insert([
                'id' => $id,
                'user_id' => $userId,
                'payload' => '',
                'last_activity' => time(),
            ]);
        }

        $this->actingAs($admin)
            ->put(route('members.password', $target), ['password' => 'brand-new-password'])
            ->assertSessionHasNoErrors();

        $target->refresh();
        expect(password_verify('brand-new-password', $target->password))->toBeTrue()
            ->and($target->remember_token)->not->toBe('old-token')
            ->and(DB::table('sessions')->where('user_id', $target->id)->count())->toBe(0)
            ->and(DB::table('sessions')->where('user_id', $other->id)->count())->toBe(1);
    });

    test('a short password is rejected and nothing changes', function () {
        $target = User::factory()->create();
        $before = $target->password;

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('members.password', $target), ['password' => 'short'])
            ->assertSessionHasErrors('password');

        expect($target->fresh()->password)->toBe($before);
    });

    // The current session is spared in the controller (it is excluded by id); the
    // test session driver is "array", so only the other-sessions part is asserted.
    test('an admin resetting their own password ends their other sessions', function () {
        $admin = User::factory()->admin()->create();

        DB::table('sessions')->insert([
            'id' => 'admin-other-session',
            'user_id' => $admin->id,
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($admin)
            ->put(route('members.password', $admin), ['password' => 'brand-new-password'])
            ->assertSessionHasNoErrors();

        expect(DB::table('sessions')->where('id', 'admin-other-session')->exists())->toBeFalse();
    });
});

describe('generated passwords', function () {
    // Real output of generatePassword() in resources/js/lib/generate-password.ts
    // (16 characters; lower case, upper case, a digit, and a symbol). The
    // generator itself was checked separately under Node over 20,000 runs.
    test('pass the production password rule', function (string $generated) {
        $this->app['env'] = 'production';
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $validator = Validator::make(
            ['password' => $generated],
            ['password' => ['required', 'string', Password::default()]],
        );

        expect($validator->passes())->toBeTrue();
    })->with([
        'UmEkvy9uy4s=-n#*',
        'hwEvrUh?Umye!9ER',
        'G7%?WVP3$9%hxr4a',
    ]);

    test('the production rule really is stricter than the local one', function () {
        $this->app['env'] = 'production';
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $validator = Validator::make(
            ['password' => 'alllowercase1'],
            ['password' => ['required', 'string', Password::default()]],
        );

        expect($validator->passes())->toBeFalse();
    });
});

describe('levels', function () {
    test('rows carry the real level, title, and quests done', function () {
        $admin = User::factory()->admin()->create();
        $hero = User::factory()->create();
        Quest::factory()->done()->create(['adventurer_id' => $hero->id, 'difficulty' => Difficulty::Hard]);
        Quest::factory()->done()->create(['adventurer_id' => $hero->id, 'difficulty' => Difficulty::Easy]);
        Quest::factory()->inReview()->create(['adventurer_id' => $hero->id]);

        $this->actingAs($admin)
            ->get(route('members.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('members.0.id', $admin->id)
                ->where('members.0.level', 1)
                ->where('members.0.level_title', 'Apprentice')
                ->where('members.0.quests_done', 0)
                ->where('members.1.id', $hero->id)
                ->where('members.1.level', 2)
                ->where('members.1.level_title', 'Squire')
                ->where('members.1.quests_done', 2));
    });

    test('the query count does not grow with the number of members', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create();
        $this->actingAs($admin);
        Setting::current(); // warm the branding row so its first-use queries are not counted

        DB::enableQueryLog();
        $this->get(route('members.index'))->assertOk();
        $small = count(DB::getQueryLog());

        User::factory()->count(4)->create()->each(
            fn (User $user) => Quest::factory()->done()->create(['adventurer_id' => $user->id]),
        );

        DB::flushQueryLog();
        $this->get(route('members.index'))->assertOk();
        $large = count(DB::getQueryLog());

        expect($large)->toBe($small);
    });
});
