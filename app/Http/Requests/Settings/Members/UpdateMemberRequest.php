<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings\Members;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMemberRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return $this->user()?->can('update', $target) ?? false;
    }

    /**
     * Emails are stored lowercase, so compare them lowercase.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            ...$this->profileRules($target->id),
            'portrait' => ['nullable', 'string', Rule::in(array_keys(config('quests.portraits')))],
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

                if (config('demo.enabled') && $this->input('email') !== $target->email) {
                    $validator->errors()->add(
                        'email',
                        "Email addresses can't be changed in the demo, so everyone can keep signing in.",
                    );
                }
            },
        ];
    }
}
