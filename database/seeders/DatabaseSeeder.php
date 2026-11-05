<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Local development and the public demo only. Every account uses the
     * password "password".
     */
    public function run(): void
    {
        if (! app()->isLocal() && ! config('demo.enabled')) {
            return;
        }

        User::factory()->admin()->withPortrait('portrait-01')->create([
            'name' => 'Ada Quill',
            'email' => 'admin@example.com',
        ]);

        User::factory()->lead()->withPortrait('portrait-02')->create([
            'name' => 'Grace Marlow',
            'email' => 'grace.marlow@example.com',
        ]);

        User::factory()->withPortrait('portrait-03')->create([
            'name' => 'Kit Thornton',
            'email' => 'kit.thornton@example.com',
        ]);

        User::factory()->withPortrait('portrait-04')->create([
            'name' => 'Alan Brooks',
            'email' => 'alan.brooks@example.com',
        ]);

        // No portrait: shows the initials fallback.
        User::factory()->create([
            'name' => 'Lin Torres',
            'email' => 'lin.torres@example.com',
        ]);

        $this->call(QuestSeeder::class);
    }
}
