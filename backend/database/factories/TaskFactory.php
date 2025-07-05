<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dueDate = $this->faker->optional(0.7)->dateTimeBetween('now', '+2 months');
        $isCompleted = $this->faker->boolean(30); // 30%の確率で完了
        
        return [
            'user_id' => User::factory(),
            'category_id' => $this->faker->optional(0.8) ? Category::factory() : null,
            'status_id' => TaskStatus::factory(),
            'title' => $this->faker->sentence(rand(3, 8)),
            'description' => $this->faker->optional(0.6)->paragraph(rand(1, 3)),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'due_date' => $dueDate,
            'completed_at' => $isCompleted ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
        ];
    }

    /**
     * 高優先度タスクの状態
     */
    public function highPriority(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * 完了済みタスクの状態
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * 期限切れタスクの状態
     */
    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'completed_at' => null,
        ]);
    }

    /**
     * 特定ユーザーのタスク
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * 特定カテゴリのタスク
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn(array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
