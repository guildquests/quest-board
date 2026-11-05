<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quests:make-admin {email : Email of an existing user}')]
#[Description('Make an existing user an admin (used to create the first admin in production)')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = mb_strtolower($this->input()->string('email')->value());

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("No user with email {$email}.");

            return self::FAILURE;
        }

        $user->role = Role::Admin;
        $user->save();

        $this->info("{$user->name} is now an admin.");

        return self::SUCCESS;
    }
}
