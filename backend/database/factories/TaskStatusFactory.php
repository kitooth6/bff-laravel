<?php

namespace Database\Factories;

use App\Models\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskStatusFactory extends Factory
{
    protected $model = TaskStatus::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                '未着手', '進行中', '完了', '保留', '一時停止', 
                'レビュー中', '承認待ち', '差し戻し', 'キャンセル'
            ]) . '_' . $this->faker->randomNumber(3),
            'color' => $this->faker->hexColor(),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * 未着手ステータス
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => '未着手',
            'color' => '#6B7280',
            'sort_order' => 1,
        ]);
    }

    /**
     * 進行中ステータス
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => '進行中',
            'color' => '#F59E0B',
            'sort_order' => 2,
        ]);
    }

    /**
     * 完了ステータス
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => '完了',
            'color' => '#10B981',
            'sort_order' => 3,
        ]);
    }
}
