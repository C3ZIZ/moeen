<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    public function owner()
    {   
        // this workspace belongs to a user who is the owner
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function members()
    {
        // this workspace has many users through the pivot table workspace_user
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }
    public function projects()
    {
        // this workspace has many projects
        return $this->hasMany(Project::class);
    }

}
