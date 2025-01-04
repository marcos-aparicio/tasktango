<?php
namespace App\Traits\Models\Project;

use App\Enums\ProjectUserRoles;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectNote;
use App\Models\SubProject;
use App\Models\Task;
use App\Models\User;

trait Relations
{
    public function users()
    {
        return $this
            ->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function user()
    {
        return $this
            ->users()
            ->where('user_id', auth()->id())
            ->first();
    }

    public function userByInstance(User $user)
    {
        return $this->users()->where('user_id', $user->id)->first();
    }

    public function owner()
    {
        return $this
            ->users()
            ->wherePivot('role', ProjectUserRoles::OWNER->value);
    }

    public function getOwnerAttribute()
    {
        return $this->owner()->first();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function labels()
    {
        return $this->hasMany(Label::class, 'project_id');
    }

    public function invitations()
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    public function validInvitations()
    {
        return $this
            ->invitations()
            ->where(function ($query) {
                $query
                    ->where('valid_until', '>', now())
                    ->orWhereNull('valid_until');
            })
            ->whereNull('accepted_at')
            ->whereNull('rejected_at');
    }

    public function notes()
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function subprojects()
    {
        return $this->hasMany(SubProject::class);
    }
}
