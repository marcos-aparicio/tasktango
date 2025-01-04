<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    // define the fillable fields
    protected $fillable = ['content'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userUsername(): Attribute
    {
        return new Attribute(
            get: function () {
                if (isset($this->task->project) && $this->task->has('project') && !$this->task->project->users->contains($this->user))
                    return 'Previous Member';

                return $this->user->username;
            },
        );
    }

    public function userFullName(): Attribute
    {
        return new Attribute(
            get: function () {
                if (isset($this->task->project) && $this->task->has('project') && !$this->task->project->users->contains($this->user))
                    return 'Previous Member';

                return $this->user->full_name;
            },
        );
    }
}
