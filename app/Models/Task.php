<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public function project()
    {
        // this task belongs to a project
        return $this->belongsTo(Project::class);
    }
    public function comments()
    {
        // this task has many comments
        return $this->hasMany(Comment::class);
    }
    public function reminders()
    {
        // this task has many reminders
        return $this->hasMany(Reminder::class);
    }

}
