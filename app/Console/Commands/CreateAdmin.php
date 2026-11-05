<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('quests:create-admin {--name= : Full name} {--email= : Email address} {--password= : Password (visible in the process list; prefer the prompt)}')]
#[Description('Create a new admin account (used to create the first admin, since there is no sign-up)')]
class CreateAdmin extends Command
{
    use ProfileValidationRules;

    /**
     * Execute the console command.
     *
     * Anything not given as an option is asked for, unless the command runs with --no-interaction.
     */
    public function handle(): int
    {
        $interactive = $this->input->isInteractive();

        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        if ($interactive) {
            $name ??= text(
                label: 'Name',
                required: true,
                validate: fn (string $value): ?string => $this->problem('name', $value),
            );
            $email ??= text(
                label: 'Email',
                required: true,
                validate: fn (string $value): ?string => $this->problem('email', $this->cleanEmail($value)),
            );

            if ($password === null) {
                $password = password(
                    label: 'Password',
                    required: true,
                    validate: fn (string $value): ?string => $this->problem('password', $value),
                );
                password(
                    label: 'Confirm password',
                    required: true,
                    validate: fn (string $value): ?string => $value === $password ? null : 'The passwords do not match.',
                );
            }
        }

        $data = [
            'name' => is_string($name) ? trim($name) : null,
            'email' => is_string($email) ? $this->cleanEmail($email) : null,
            'password' => $password,
        ];

        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::create([...$data, 'role' => Role::Admin]);

        $this->info("Admin {$user->email} is ready. They can log in now.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => ['required', 'string', Password::default()],
        ];
    }

    /** Emails are stored lowercase, so compare them lowercase. */
    private function cleanEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** The first validation message for one field, for a prompt to show. */
    private function problem(string $field, string $value): ?string
    {
        $validator = Validator::make([$field => $value], [$field => $this->rules()[$field]]);

        return $validator->fails() ? $validator->errors()->first($field) : null;
    }
}
