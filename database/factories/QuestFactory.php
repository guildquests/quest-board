<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quest>
 */
class QuestFactory extends Factory
{
    /**
     * Define the model's default state: an open quest.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Set here too: the seeder runs without model events, so the creating hook is skipped.
            'public_id' => bin2hex(random_bytes(12)),
            'poster_id' => User::factory(),
            'title' => fake()->text(40),
            'summary' => fake()->text(80),
            'category' => fake()->randomElement(Category::cases()),
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'brief' => fake()->paragraph(),
            'objectives' => fake()->sentences(3),
            'proof' => fake()->sentence(),
            'status' => QuestStatus::Open,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            $claimedAt = now()->subDay();

            return [
                'status' => QuestStatus::InProgress,
                'adventurer_id' => User::factory(),
                'claimed_at' => $claimedAt,
                'due_at' => $claimedAt->copy()->addDays((int) config('quests.claim_days')),
            ];
        });
    }

    public function inReview(): static
    {
        return $this->inProgress()->state(fn (array $attributes) => [
            'status' => QuestStatus::InReview,
            'result_url' => fake()->url(),
            'result_note' => fake()->sentence(),
            'turned_in_at' => now(),
        ]);
    }

    public function done(): static
    {
        return $this->inReview()
            ->state(fn (array $attributes) => [
                'status' => QuestStatus::Done,
                'poster_xp' => (int) config('quests.poster_xp'),
                'approved_by' => User::factory()->lead(),
                'approved_at' => now(),
            ])
            // After overrides, so a difficulty passed to create() sets the XP.
            ->afterMaking(function (Quest $quest): void {
                $quest->xp ??= $quest->difficulty->xp();
            });
    }
}
