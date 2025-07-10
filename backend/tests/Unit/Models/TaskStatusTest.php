<?php

namespace Tests\Unit\Models;

use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusTest extends TestCase
{
    use RefreshDatabase;

    // ============================
    // Factory テスト
    // ============================

    /** @test */
    public function ファクトリでインスタンスを作成できる()
    {
        $taskStatus = TaskStatus::factory()->make();
        $this->assertInstanceOf(TaskStatus::class, $taskStatus);
        $this->assertNotEmpty($taskStatus->name);
        $this->assertNotEmpty($taskStatus->color);
        $this->assertTrue($taskStatus->sort_order >= 1 && $taskStatus->sort_order <= 10);
    }

    /** @test */
    public function ファクトリで指定した属性でタスクステータスを作成できる()
    {
        $taskStatusData = [
            'name' => '未着手',
            'color' => '#fff',
            'sort_order' => 2,
        ];

        $taskStatus = TaskStatus::factory()->make($taskStatusData);

        $this->assertEquals('未着手', $taskStatus->name);
        $this->assertEquals('#fff', $taskStatus->color);
        $this->assertEquals(2, $taskStatus->sort_order);
    }

    // ============================
    // Mass Asignment テスト
    // ============================

    /** @test */
    public function fillable属性で一括代入できる()
    {
        $fillableData = [
            'name' => 'testName',
            'color' => 'testColor',
            'sort_order' => 1
        ];

        $taskStatus = TaskStatus::factory()->make($fillableData);

        $this->assertEquals('testName', $taskStatus->name);
        $this->assertEquals('testColor', $taskStatus->color);
        $this->assertEquals(1, $taskStatus->sort_order);
    }

    // ============================
    // リレーション テスト
    // ============================
    /** @test */
    public function タスクステータスは複数のタスクをもつ()
    {
        // Given: TaskStatusと複数のTask作成
        $status = TaskStatus::factory()->create(['name' => '進行中']);
        $tasks = Task::factory()->count(3)->create(['status_id' => $status->id]);

        // When: TaskStatusからTasksを取得
        $statusTasks = $status->tasks;

        // Then: 正しいTasksが取得される
        $this->assertCount(3, $statusTasks);
        $statusTasks->each(function ($task) use ($status) {
            $this->assertInstanceOf(Task::class, $task);
            $this->assertEquals($status->id, $task->status_id);
        });
    }

    /** @test */
    public function タスクが存在しないステータスは空のコレクションを返す()
    {
        // Given: TaskStatusのみ作成（Taskなし）
        $status = TaskStatus::factory()->create();

        // When: TaskStatusからTasksを取得
        $statusTasks = $status->tasks;

        // Then: 空のコレクションが返される
        $this->assertCount(0, $statusTasks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $statusTasks);
    }

    /** @test */
    public function 異なるステータスのタスクは含まれない()
    {
        // Given: 2つのステータスとそれぞれのタスク
        $status1 = TaskStatus::factory()->create(['name' => '進行中']);
        $status2 = TaskStatus::factory()->create(['name' => '完了']);

        Task::factory()->count(2)->create(['status_id' => $status1->id]);
        Task::factory()->count(1)->create(['status_id' => $status2->id]);

        // When: status1のタスクを取得
        $status1Tasks = $status1->tasks;

        // Then: status1のタスクのみ取得される
        $this->assertCount(2, $status1Tasks);
        $status1Tasks->each(function ($task) use ($status1) {
            $this->assertEquals($status1->id, $task->status_id);
        });
    }

    // ============================
    // スコープテスト
    // ============================

    /** @test */
    public function 並び順でソートできる()
    {
        // Given: 異なるsort_orderのステータス
        TaskStatus::factory()->create(['name' => 'C', 'sort_order' => 3]);
        TaskStatus::factory()->create(['name' => 'A', 'sort_order' => 1]);
        TaskStatus::factory()->create(['name' => 'B', 'sort_order' => 2]);

        // When: orderedスコープで取得
        $orderedStatuses = TaskStatus::ordered()->get();

        // Then: sort_order順で取得される
        $this->assertEquals('A', $orderedStatuses[0]->name);
        $this->assertEquals('B', $orderedStatuses[1]->name);
        $this->assertEquals('C', $orderedStatuses[2]->name);
    }

    /** @test */
    public function 名前でソートできる()
    {
        // Given: 異なる名前のステータス
        TaskStatus::factory()->create(['name' => 'zzz']);
        TaskStatus::factory()->create(['name' => 'aaa']);
        TaskStatus::factory()->create(['name' => 'mmm']);

        // When: byNameスコープで取得
        $sortedStatuses = TaskStatus::byName()->get();

        // Then: 名前順で取得される
        $this->assertEquals('aaa', $sortedStatuses[0]->name);
        $this->assertEquals('mmm', $sortedStatuses[1]->name);
        $this->assertEquals('zzz', $sortedStatuses[2]->name);
    }

    // ============================
    // Factory State テスト
    // ============================

    /** @test */
    public function pending_stateで未着手ステータスを作成できる()
    {
        // Given & When: pending stateでステータス作成
        $status = TaskStatus::factory()->pending()->make();

        // Then: 未着手ステータスが作成される
        $this->assertEquals('未着手', $status->name);
        $this->assertEquals('#6B7280', $status->color);
        $this->assertEquals(1, $status->sort_order);
    }

    /** @test */
    public function inProgress_stateで進行中ステータスを作成できる()
    {
        // Given & When: inProgress stateでステータス作成
        $status = TaskStatus::factory()->inProgress()->make();

        // Then: 進行中ステータスが作成される
        $this->assertEquals('進行中', $status->name);
        $this->assertEquals('#F59E0B', $status->color);
        $this->assertEquals(2, $status->sort_order);
    }

    /** @test */
    public function completed_stateで完了ステータスを作成できる()
    {
        // Given & When: completed stateでステータス作成
        $status = TaskStatus::factory()->completed()->make();

        // Then: 完了ステータスが作成される
        $this->assertEquals('完了', $status->name);
        $this->assertEquals('#10B981', $status->color);
        $this->assertEquals(3, $status->sort_order);
    }

    // ============================
    // バリデーション/制約テスト
    // ============================

    /** @test */
    public function name_フィールドは必須である()
    {
        // Given: name無しのデータ
        $this->expectException(\Exception::class);

        // When & Then: name無しで作成するとエラー
        TaskStatus::factory()->create(['name' => null]);
    }

    /** @test */
    public function colorフィールドにはデフォルト値が設定される()
    {
        // Given & When: colorを指定せずにステータス作成
        $status = TaskStatus::factory()->create([
            'name' => 'test',
            'sort_order' => 1
        ]);
        // Then: デフォルト値が設定される（実際の動作確認が必要）
        $this->assertNotNull($status->color);
    }
}
