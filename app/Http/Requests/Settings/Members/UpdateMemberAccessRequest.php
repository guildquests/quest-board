<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings\Members;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMemberAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return $this->user()?->can('updateAccess', $target) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
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
                    && $this->has('active')
                    && ! $this->boolean('active')
                ) {
                    $validator->errors()->add(
                        'active',
                        "Seeded demo accounts can't be deactivated, so the demo stays ready for everyone.",
                    );
                }
            },
        ];
    }
}
