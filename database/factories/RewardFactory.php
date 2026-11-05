<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RewardKind;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reward>
 */
class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quest_id' => Quest::factory()->done(),
            'kind' => RewardKind::Adventurer,
            'xp' => 10,
            'xp_before' => 0,
            'seen_at' => null,
        ];
    }
}
