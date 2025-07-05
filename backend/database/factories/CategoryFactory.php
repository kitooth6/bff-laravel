<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $colors = [
            '#FF5733',
            '#33FF57',
            '#3357FF',
            '#FF33F1',
            '#F1FF33',
            '#33FFF1',
            '#FF8C33',
            '#8C33FF'
        ];

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(([
                '仕事',
                'プライベート',
                '学習',
                '健康',
                '家事',
                '趣味',
                'ショッピング',
                '旅行',
                '読書',
                '運動'
            ])),
            'color' => $this->faker->randomElement($colors),
            'description' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    /**
     * 特定ユーザーのカテゴリ
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * 仕事カテゴリ
     */
    public function work(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => '仕事',
            'color' => '#FF5733',
        ]);
    }
}
