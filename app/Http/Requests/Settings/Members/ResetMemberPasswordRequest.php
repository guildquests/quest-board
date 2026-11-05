<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings\Members;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class ResetMemberPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return $this->user()?->can('resetPassword', $target) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::default()],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (config('demo.enabled')) {
                    $validator->errors()->add(
                        'password',
                        "Passwords can't be reset in the demo, so everyone can keep signing in.",
                    );
                }
            },
        ];
    }
}
