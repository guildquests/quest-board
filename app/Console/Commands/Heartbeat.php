<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Temporary: proves the scheduler runs by logging a line every minute.
 * Replaced by the claim-expiry command in the Quests feature.
 */
#[Signature('quests:heartbeat')]
#[Description('Log a heartbeat line to check that the scheduler is running')]
class Heartbeat extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::info('Heartbeat '.now()->toIso8601String());

        return self::SUCCESS;
    }
}
