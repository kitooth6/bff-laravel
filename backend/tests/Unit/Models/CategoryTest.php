<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // ===================
    // Factoryテスト
    // ===================

    /** @test */
    public function ファクトリでインスタンスを作成できる()
    {
        $category = Category::factory()->make();
        $this->assertInstanceOf(Category::class, $category);
        $this->assertNotEmpty($category->user_id);
        $this->assertNotEmpty($category->name);
        $this->assertNotEmpty($category->color);
    }

    /** @test */
    public function ファクトリで指定した属性でカテゴリを作成できる()
    {
        $category = Category::factory()->make([
            'name' => '学習',
            'color' => '#8C33FF',
            'description' => 'test description'
        ]);
        $this->assertEquals('学習', $category->name);
        $this->assertEquals('#8C33FF', $category->color);
        $this->assertEquals('test description', $category->description);
    }

    /** @test */
    public function 特定ユーザーのカテゴリを作成できる()
    {
        $user = User::factory()->create([
            'name' => 'testuser'
        ]);
        $category = Category::factory()->forUser($user)->make();
        $this->assertEquals($user->id, $category->user_id);
    }

    /** @test */
    public function 仕事用のカテゴリを作成できる()
    {
        $work = Category::factory()->work()->make();
        $this->assertEquals('仕事', $work->name);
        $this->assertEquals('#FF5733', $work->color);
    }

    // ===================
    // Mass Assignmentテスト
    // ===================

    /** @test */
    public function fillable属性で一括代入できる()
    {
        $fillableData = [
            'name' => 'testName',
            'color' => 'testColor',
            'description' => 'testDescription'
        ];
        $category = Category::factory()->make($fillableData);
        $this->assertEquals('testName', $category->name);
        $this->assertEquals('testColor', $category->color);
        $this->assertEquals('testDescription', $category->description);
    }

    // ===================
    // リレーションテスト
    // ===================

    /** @test */
    public function カテゴリーはユーザーに属する()
    {
        $user = User::factory()->create(['name' => 'testUser']);
        $category = Category::factory()->create([
            'user_id' => $user->id
        ]);
        $categoryUser = $category->user;
        $this->assertInstanceOf(User::class, $categoryUser);
        $this->assertEquals($user->id, $categoryUser->id);
        $this->assertEquals('testUser', $categoryUser->name);
    }

    /** @test */
    public function カテゴリーは複数のタスクを持つ()
    {
        $category = Category::factory()->create(['name' => '勉強']);
        $tasks = Task::factory()->count(3)->create(['category_id' => $category->id]);
        $categoryTasks = $category->tasks;
        $this->assertCount(3, $categoryTasks);
        $categoryTasks->each(function ($task) use ($category) {
            $this->assertInstanceOf(Task::class, $task);
            $this->assertEquals($category->id, $task->category_id);
        });
    }

    // ===================
    // スコープテスト
    // ===================
    /** @test */
    public function 名前でソートできる()
    {
        Category::factory()->create(['name' => 'B']);
        Category::factory()->create(['name' => 'C']);
        Category::factory()->create(['name' => 'A']);
        $orderedByNameCategory = Category::orderByName()->get();
        $this->assertEquals('A', $orderedByNameCategory[0]->name);
        $this->assertEquals('B', $orderedByNameCategory[1]->name);
        $this->assertEquals('C', $orderedByNameCategory[2]->name);
    }

    /** @test */
    public function 特定の名前のカテゴリの取得ができる()
    {
        Category::factory()->create(['name' => 'A']);
        Category::factory()->create(['name' => 'B']);
        $categoryA = Category::byName('A')->first();
        $categoryB = Category::byName('B')->first();
        // dd($categoryA);

        $this->assertEquals('A', $categoryA->name);
        $this->assertEquals('B', $categoryB->name);
    }

    /** @test */
    public function 指定ユーザーのカテゴリを絞り込める()
    {
        // Given: 複数ユーザーのカテゴリ
        $user1 = User::factory()->create(['name' => 'ユーザー1']);
        $user2 = User::factory()->create(['name' => 'ユーザー2']);

        Category::factory()->count(3)->create(['user_id' => $user1->id]);
        Category::factory()->count(2)->create(['user_id' => $user2->id]);

        // When: ユーザー1のカテゴリを取得
        $user1Categories = Category::forUser($user1->id)->get();

        // Then: ユーザー1のカテゴリのみ取得される
        $this->assertCount(3, $user1Categories);
        $user1Categories->each(function ($category) use ($user1) {
            $this->assertEquals($user1->id, $category->user_id);
        });
    }

    /** @test */
    public function 複数のスコープを組み合わせて絞り込める()
    {
        // Given: 複数条件のカテゴリ
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Category::factory()->count(2)->create([
            'user_id' => $user1->id,
            'name' => '仕事'
        ]);
        Category::factory()->count(1)->create([
            'user_id' => $user2->id,
            'name' => '仕事'
        ]);

        // When: 複数スコープを連鎖
        $user1WorkCategories = Category::forUser($user1->id)
            ->byName('仕事')
            ->get();

        // Then: 条件を満たすカテゴリのみ取得される
        $this->assertCount(2, $user1WorkCategories);
    }

    /** @test */
    public function タスクが存在しないカテゴリは空のコレクションを返す()
    {
        // Given: カテゴリのみ作成（タスクなし）
        $category = Category::factory()->create();

        // When: カテゴリからタスクを取得
        $categoryTasks = $category->tasks;

        // Then: 空のコレクションが返される
        $this->assertCount(0, $categoryTasks);
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Collection::class,
            $categoryTasks
        );
    }
}
