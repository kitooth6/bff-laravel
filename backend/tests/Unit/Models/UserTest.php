<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // Factory　テスト
    /** @test */
    public function ファクトリでユーザーインスタンスを作成できる()
    {
        $user = User::factory()->make();
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
    }

    // Mass Assignmen テスト
    /** @test */
    public function fillable属性で一括代入できる()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        $user = User::factory()->make($userData);
        $this->assertEquals('テストユーザー', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('password123', $user->password);
    }

    // リレーションテスト
    /** @test */
    public function ユーザーは複数のタスクを持つ()
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);
        $this->assertCount(3, $user->tasks);
    }

    /** @test */
    public function ユーザーは複数のカテゴリを持つ()
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create(['user_id' => $user->id]);
        $this->assertCount(3, $user->categories);
    }

    // スコープテスト
    /** @test */
    public function メールアドレスでユーザーを絞り込める()
    {
        User::factory()->create(['email' => 'test1@example.com']);
        User::factory()->create(['email' => 'test2@example.com']);
        $user = User::byEmail('test1@example.com')->first();
        $this->assertEquals('test1@example.com', $user->email);
    }

    /** @test */
    public function 認証済みユーザーを絞り込める()
    {
        User::factory()->create(['name' => 'test1', 'email_verified_at' => now()]);
        User::factory()->create(['name' => 'test2', 'email_verified_at' => null]);
        $user = User::verified()->first();
        $this->assertEquals('test1', $user->name);
    }

    /** @test */
    public function 名前でユーザーをソートできる()
    {
        User::factory()->create(['name' => 'test1']);
        User::factory()->create(['name' => 'test2']);
        User::factory()->create(['name' => 'test3']);
        $users = User::orderByName()->get();
        $this->assertEquals('test1', $users[0]->name);
        $this->assertEquals('test2', $users[1]->name);
        $this->assertEquals('test3', $users[2]->name);
    }

    // アクセサテスト
    /** @test */
    public function メール認証済み状態を正しく判定できる()
    {
        $verifedUser = User::factory()->make(['email_verified_at' => now()]);
        $unverifiedUser = User::factory()->make(['email_verified_at' => null]);

        $this->assertTrue($verifedUser->verified);
        $this->assertFalse($unverifiedUser->verified);
    }
}
