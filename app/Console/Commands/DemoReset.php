<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

#[Signature('quests:demo-reset')]
#[Description('Wipe and re-seed the public demo (demo mode only)')]
class DemoReset extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (config('demo.enabled') !== true) {
            $this->error('Demo mode is off (DEMO_MODE=false). This command wipes all data, so it only runs on the public demo.');

            return self::FAILURE;
        }

        Log::info('Demo reset started.');

        try {
            if ($this->call('down', ['--retry' => 60]) !== self::SUCCESS) {
                throw new RuntimeException('Could not enable maintenance mode.');
            }

            // AppServiceProvider blocks destructive commands in production. Lift the
            // block for migrate:fresh (and the db:wipe it calls) during this reset only.
            FreshCommand::prohibit(false);
            WipeCommand::prohibit(false);

            if ($this->call('migrate:fresh', ['--seed' => true, '--force' => true]) !== self::SUCCESS) {
                throw new RuntimeException('Database reset failed.');
            }

            $this->deleteUploads();

            if ($this->call('cache:clear') !== self::SUCCESS) {
                throw new RuntimeException('Cache clear failed.');
            }
        } catch (Throwable $exception) {
            Log::error('Demo reset failed: '.$exception->getMessage());
            $this->error('Demo reset failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            FreshCommand::prohibit(app()->isProduction());
            WipeCommand::prohibit(app()->isProduction());
            $this->call('up');
        }

        Log::info('Demo reset finished.');
        $this->info('Demo reset finished.');

        return self::SUCCESS;
    }

    private function deleteUploads(): void
    {
        $disk = Storage::disk('public');
        $files = array_values(array_filter(
            $disk->allFiles(),
            fn (string $path): bool => $path !== '.gitignore',
        ));

        if ($files !== [] && ! $disk->delete($files)) {
            throw new RuntimeException('Uploaded files could not be deleted.');
        }

        $directories = $disk->allDirectories();
        rsort($directories);

        foreach ($directories as $directory) {
            if (! $disk->deleteDirectory($directory)) {
                throw new RuntimeException("Uploaded directory [{$directory}] could not be deleted.");
            }
        }
    }
}
