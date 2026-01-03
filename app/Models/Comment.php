<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{

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
