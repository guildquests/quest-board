<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PingJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quests:ping')]
#[Description('Dispatch a PingJob to check that the queue worker is running')]
class PingQueue extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        PingJob::dispatch();

        $this->info('PingJob dispatched');

        return self::SUCCESS;
    }
}
