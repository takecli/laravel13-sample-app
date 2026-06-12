<?php

namespace Database\Factories;

use App\Domains\Enums\Team\PublicStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
final class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'public_status' => PublicStatus::Public,
            // 実在する users レコードを参照(作成者=更新者として同一ユーザーを使う)
            'created_user_id' => User::factory(),
            'updated_user_id' => fn (array $attributes): string => $attributes['created_user_id'],
        ];
    }

    /**
     * 招待制チーム。
     */
    public function invitation(): static
    {
        return $this->state(fn (): array => [
            'public_status' => PublicStatus::Invitation,
        ]);
    }
}
