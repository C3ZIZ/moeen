<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    public function task()
    {
        // this reminder belongs to a task
        return $this->belongsTo(Task::class);
    }
}
