<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProject extends Model
{
    protected $table = 'subprojects';
    protected $fillable = ['name'];

    //
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
