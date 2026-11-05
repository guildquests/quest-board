<?php

declare(strict_types=1);

use App\Enums\Role;

test('roles have labels and descriptions', function (Role $role, string $label) {
    expect($role->label())->toBe($label)
        ->and($role->description())->not->toBeEmpty();
})->with([
    'member' => [Role::Member, 'Member'],
    'lead' => [Role::Lead, 'Lead'],
    'admin' => [Role::Admin, 'Admin'],
]);

test('only leads and admins can review', function (Role $role, bool $canReview) {
    expect($role->canReview())->toBe($canReview);
})->with([
    'member' => [Role::Member, false],
    'lead' => [Role::Lead, true],
    'admin' => [Role::Admin, true],
]);
