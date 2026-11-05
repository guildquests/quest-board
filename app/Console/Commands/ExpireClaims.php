<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\QuestService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quests:expire-claims')]
#[Description('Send In progress quests past their deadline back to Open')]
class ExpireClaims extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(QuestService $quests): int
    {
        $this->info("Expired {$quests->expireOverdueClaims()} claim(s).");

        return self::SUCCESS;
    }
}
