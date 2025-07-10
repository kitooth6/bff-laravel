<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    // mass asignment
    protected $fillable = [
        'user_id',
        'category_id',
        'status_id',
        'title',
        'description',
        'priority',
        'due_date',
        'completed_at'
    ];

    // キャスト
    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // リレーション
    /**
     * User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * TaskStatus
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class);
    }

    // アクセサ
    /**
     * 優先度の日本語ラベル
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            default => '不明'
        };
    }

    /**
     * 完了済みかどうか
     */
    public function getIsCompletedAttribute(): bool
    {
        return !is_null($this->completed_at);
    }

    /**
     * 期限切れかどうか
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date &&
            $this->due_date->isPast() &&
            !$this->is_completed;
    }

    // スコープ
    /**
       * 指定ユーザーのタスク
       */
      public function scopeForUser($query, int $userId)
      {
          return $query->where('user_id', $userId);
      }

      /**
       * 優先度別タスク
       */
      public function scopeByPriority($query, string $priority)
      {
          return $query->where('priority', $priority);
      }

      /**
       * 期限切れタスク
       */
      public function scopeOverdue($query)
      {
          return $query->where('due_date', '<', now())
                       ->whereNull('completed_at');
      }

      /**
       * 完了済みタスク
       */
      public function scopeCompleted($query)
      {
          return $query->whereNotNull('completed_at');
      }

      /**
       * 未完了タスク
       */
      public function scopePending($query)
      {
          return $query->whereNull('completed_at');
      }
}
