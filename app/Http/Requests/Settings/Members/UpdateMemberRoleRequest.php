<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings\Members;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return $this->user()?->can('updateRole', $target) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $target */
                $target = $this->route('user');
                $demoAccounts = config('demo.accounts', []);

                if (
                    config('demo.enabled')
                    && is_array($demoAccounts)
                    && in_array($target->email, $demoAccounts, true)
                    && $this->input('role') !== $target->role->value
                ) {
                    $validator->errors()->add(
                        'role',
                        "Seeded demo accounts can't change roles, so the demo stays ready for everyone.",
                    );
                }
            },
        ];
    }
}
