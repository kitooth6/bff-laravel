<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'sort_order'
    ];

    /**
     * Tasks
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_id');
    }

    /**
     * 並び順でソート
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * 名前でソート
     */
    public function scopeByName($query)
    {
        return $query->orderBy('name');
    }
}
