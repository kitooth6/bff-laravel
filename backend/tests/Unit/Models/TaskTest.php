<?php
// tests/Unit/Models/TaskTest.php

namespace Tests\Unit\Models;

use App\Models\Task;
use App\Models\User;
use App\Models\Category;
use App\Models\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    // ===================================
    // Factory テスト
    // ===================================

    /** @test */
    public function ファクトリでタスクインスタンスを作成できる()
    {
        // Given & When: Factoryでタスク作成
        $task = Task::factory()->make();

        // Then: 正しいインスタンスが作成される
        $this->assertInstanceOf(Task::class, $task);
        $this->assertNotEmpty($task->title);
        $this->assertContains($task->priority, ['low', 'medium', 'high']);
    }

    /** @test */
    public function ファクトリで指定した属性でタスクを作成できる()
    {
        // Given: 指定データ
        $taskData = [
            'title' => 'テストタスク',
            'description' => 'テスト用の説明文',
            'priority' => 'high',
            'due_date' => '2024-12-31'
        ];

        // When: 指定データでタスク作成
        $task = Task::factory()->make($taskData);

        // Then: 指定した値が設定される
        $this->assertEquals('テストタスク', $task->title);
        $this->assertEquals('テスト用の説明文', $task->description);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals('2024-12-31', $task->due_date->format('Y-m-d'));
    }

    // ===================================
    // Mass Assignment テスト
    // ===================================

    /** @test */
    public function fillable属性で一括代入できる()
    {
        // Given: 許可されたフィールドのデータ
        $fillableData = [
            'title' => '一括代入テスト',
            'description' => '説明文',
            'priority' => 'medium',
            'due_date' => now()->addDays(7)
        ];

        // When: 一括代入でタスク作成
        $task = Task::factory()->make($fillableData);

        // Then: 全ての値が正しく設定される
        $this->assertEquals('一括代入テスト', $task->title);
        $this->assertEquals('説明文', $task->description);
        $this->assertEquals('medium', $task->priority);
    }

    // ===================================
    // リレーションテスト
    // ===================================

    /** @test */
    public function タスクはユーザーに属する()
    {
        // Given: ユーザーとそのタスクを作成
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $status = TaskStatus::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status_id' => $status->id
        ]);

        // When: タスクからユーザーを取得
        $taskUser = $task->user;

        // Then: 正しいユーザーが取得される
        $this->assertInstanceOf(User::class, $taskUser);
        $this->assertEquals($user->id, $taskUser->id);
        $this->assertEquals('テストユーザー', $taskUser->name);
    }

    /** @test */
    public function タスクはカテゴリに属する()
    {
        // Given: カテゴリとそのタスクを作成
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => '仕事',
            'user_id' => $user->id
        ]);
        $status = TaskStatus::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status_id' => $status->id
        ]);

        // When: タスクからカテゴリを取得
        $taskCategory = $task->category;

        // Then: 正しいカテゴリが取得される
        $this->assertInstanceOf(Category::class, $taskCategory);
        $this->assertEquals($category->id, $taskCategory->id);
        $this->assertEquals('仕事', $taskCategory->name);
    }

    /** @test */
    public function タスクはステータスに属する()
    {
        // Given: ステータスとそのタスクを作成
        $status = TaskStatus::factory()->create(['name' => '進行中']);
        $task = Task::factory()->create(['status_id' => $status->id]);

        // When: タスクからステータスを取得
        $taskStatus = $task->status;

        // Then: 正しいステータスが取得される
        $this->assertInstanceOf(TaskStatus::class, $taskStatus);
        $this->assertEquals($status->id, $taskStatus->id);
        $this->assertEquals('進行中', $taskStatus->name);
    }

    // ===================================
    // アクセサテスト
    // ===================================

    /** @test */
    public function 優先度ラベルを日本語で取得できる()
    {
        // Given: 各優先度のタスク
        $lowTask = Task::factory()->make(['priority' => 'low']);
        $mediumTask = Task::factory()->make(['priority' => 'medium']);
        $highTask = Task::factory()->make(['priority' => 'high']);

        // When & Then: 正しい日本語ラベルが返される
        $this->assertEquals('低', $lowTask->priority_label);
        $this->assertEquals('中', $mediumTask->priority_label);
        $this->assertEquals('高', $highTask->priority_label);
    }

    /** @test */
    public function 完了状態を正しく判定できる()
    {
        // Given: 完了済みと未完了のタスク
        $completedTask = Task::factory()->make([
            'completed_at' => Carbon::now()
        ]);
        $pendingTask = Task::factory()->make([
            'completed_at' => null
        ]);

        // When & Then: 完了状態が正しく判定される
        $this->assertTrue($completedTask->is_completed);
        $this->assertFalse($pendingTask->is_completed);
    }

    /** @test */
    public function 期限切れ状態を正しく判定できる()
    {
        // Given: 様々な期限のタスク
        $overdueTask = Task::factory()->make([
            'due_date' => Carbon::yesterday(),
            'completed_at' => null
        ]);

        $futureTask = Task::factory()->make([
            'due_date' => Carbon::tomorrow(),
            'completed_at' => null
        ]);

        $completedOverdueTask = Task::factory()->make([
            'due_date' => Carbon::yesterday(),
            'completed_at' => Carbon::now()
        ]);

        // When & Then: 期限切れ状態が正しく判定される
        $this->assertTrue(
            $overdueTask->is_overdue,
            '期限切れ未完了タスクは期限切れと判定される'
        );
        $this->assertFalse(
            $futureTask->is_overdue,
            '期限内タスクは期限切れと判定されない'
        );
        $this->assertFalse(
            $completedOverdueTask->is_overdue,
            '完了済みタスクは期限切れと判定されない'
        );
    }

    // ===================================
    // スコープテスト
    // ===================================

    /** @test */
    public function 指定ユーザーのタスクを絞り込める()
    {
        // Given: 複数ユーザーのタスク
        $user1 = User::factory()->create(['name' => 'ユーザー1']);
        $user2 = User::factory()->create(['name' => 'ユーザー2']);

        Task::factory()->count(3)->create(['user_id' => $user1->id]);
        Task::factory()->count(2)->create(['user_id' => $user2->id]);

        // When: ユーザー1のタスクを取得
        $user1Tasks = Task::forUser($user1->id)->get();

        // Then: ユーザー1のタスクのみ取得される
        $this->assertCount(3, $user1Tasks);
        $user1Tasks->each(function ($task) use ($user1) {
            $this->assertEquals($user1->id, $task->user_id);
        });
    }

    /** @test */
    public function 優先度でタスクを絞り込める()
    {
        // Given: 異なる優先度のタスク
        Task::factory()->count(2)->create(['priority' => 'high']);
        Task::factory()->count(3)->create(['priority' => 'medium']);
        Task::factory()->count(1)->create(['priority' => 'low']);

        // When: 高優先度タスクを取得
        $highPriorityTasks = Task::byPriority('high')->get();

        // Then: 高優先度タスクのみ取得される
        $this->assertCount(2, $highPriorityTasks);
        $highPriorityTasks->each(function ($task) {
            $this->assertEquals('high', $task->priority);
        });
    }

    /** @test */
    public function 期限切れタスクを絞り込める()
    {
        // Given: 期限切れと期限内のタスク
        Task::factory()->count(2)->create([
            'due_date' => Carbon::yesterday(),
            'completed_at' => null
        ]);

        Task::factory()->count(1)->create([
            'due_date' => Carbon::tomorrow(),
            'completed_at' => null
        ]);

        Task::factory()->count(1)->create([
            'due_date' => Carbon::yesterday(),
            'completed_at' => Carbon::now()
        ]);

        // When: 期限切れタスクを取得
        $overdueTasks = Task::overdue()->get();

        // Then: 期限切れで未完了のタスクのみ取得される
        $this->assertCount(2, $overdueTasks);
        $overdueTasks->each(function ($task) {
            $this->assertTrue($task->due_date->isPast());
            $this->assertNull($task->completed_at);
        });
    }

    /** @test */
    public function 完了済みタスクを絞り込める()
    {
        // Given: 完了済みと未完了のタスク
        Task::factory()->count(3)->create(['completed_at' => Carbon::now()]);
        Task::factory()->count(2)->create(['completed_at' => null]);

        // When: 完了済みタスクを取得
        $completedTasks = Task::completed()->get();

        // Then: 完了済みタスクのみ取得される
        $this->assertCount(3, $completedTasks);
        $completedTasks->each(function ($task) {
            $this->assertNotNull($task->completed_at);
        });
    }

    /** @test */
    public function 未完了タスクを絞り込める()
    {
        // Given: 完了済みと未完了のタスク
        Task::factory()->count(2)->create(['completed_at' => Carbon::now()]);
        Task::factory()->count(4)->create(['completed_at' => null]);

        // When: 未完了タスクを取得
        $pendingTasks = Task::pending()->get();

        // Then: 未完了タスクのみ取得される
        $this->assertCount(4, $pendingTasks);
        $pendingTasks->each(function ($task) {
            $this->assertNull($task->completed_at);
        });
    }

    /** @test */
    public function 複数のスコープを組み合わせて絞り込める()
    {
        // Given: 複数条件のタスク
        $user = User::factory()->create(['name' => 'テストユーザー']);

        Task::factory()->count(2)->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'completed_at' => null
        ]);

        Task::factory()->count(1)->create([
            'user_id' => $user->id,
            'priority' => 'low',
            'completed_at' => null
        ]);

        Task::factory()->count(1)->create([
            'user_id' => $user->id,
            'priority' => 'high',
            'completed_at' => Carbon::now()
        ]);

        // When: 複数スコープを連鎖
        $userHighPriorityPendingTasks = Task::forUser($user->id)
            ->byPriority('high')
            ->pending()
            ->get();

        // Then: 条件を満たすタスクのみ取得される
        $this->assertCount(2, $userHighPriorityPendingTasks);
        $userHighPriorityPendingTasks->each(function ($task) use ($user) {
            $this->assertEquals($user->id, $task->user_id);
            $this->assertEquals('high', $task->priority);
            $this->assertNull($task->completed_at);
        });
    }

    // ===================================
    // キャストテスト
    // ===================================

    /** @test */
    public function 日付フィールドがCarbonインスタンスにキャストされる()
    {
        // Given: 日付を持つタスク
        $task = Task::factory()->make([
            'due_date' => '2024-12-31',
            'completed_at' => '2024-12-25 10:30:00'
        ]);

        // When & Then: Carbonインスタンスとしてアクセスできる
        $this->assertInstanceOf(Carbon::class, $task->due_date);
        $this->assertInstanceOf(Carbon::class, $task->completed_at);

        // 日付操作ができる
        $this->assertEquals('2024-12-31', $task->due_date->format('Y-m-d'));
        $this->assertEquals(
            '2024-12-25 10:30:00',
            $task->completed_at->format('Y-m-d H:i:s')
        );
    }
}
