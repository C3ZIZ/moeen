<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'content'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function task()
    {
        // this comment belongs to a task
        return $this->belongsTo(Task::class);
    }
    public function user()
    {
        // this comment belongs to a user
        return $this->belongsTo(User::class);
    }
}
