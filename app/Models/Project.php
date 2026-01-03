<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['workspace_id', 'name'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    public function workspace()
    {
        // this project belongs to a workspace
        return $this->belongsTo(Workspace::class);
    }
    public function tasks()
    {
        // this project has many tasks
        return $this->hasMany(Task::class);
    }

}
