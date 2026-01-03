<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = ['task_id', 'runs_at', 'sent_at'];

    protected function casts(): array
    {
        return [
            'runs_at' => 'datetime',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    public function task()
    {
        // this reminder belongs to a task
        return $this->belongsTo(Task::class);
    }
}
