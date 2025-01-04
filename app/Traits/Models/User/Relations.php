<?php
namespace App\Traits\Models\User;

use App\Enums\ProjectUserRoles;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectNote;
use App\Models\SubProject;
use App\Models\Task;

trait Relations
{
    /**
     * Get the tasks created by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'creator_user_id');
    }

    public function individualTasks()
    {
        return $this->tasks()->whereNull('project_id');
    }

    /**
     * Get Labels created by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function labels()
    {
        return $this->hasMany(Label::class);
    }

    public function individualLabels()
    {
        return $this->labels()->whereNull('project_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function receivedProjectInvitations()
    {
        return $this->hasMany(ProjectInvitation::class, 'invitee_id');
    }

    public function receivedProjectInvitationsValid()
    {
        return $this
            ->receivedProjectInvitations()
            ->where(function ($query) {
                $query
                    ->where('valid_until', '>', now())
                    ->orWhereNull('valid_until');
            })
            ->whereNull('accepted_at')
            ->whereNull('rejected_at');
    }

    public function sentProjectInvitations()
    {
        return $this->hasMany(ProjectInvitation::class, 'inviter_id');
    }

    public function sentProjectInvitationsValid()
    {
        return $this
            ->sentProjectInvitations()
            ->where(function ($query) {
                $query
                    ->where('valid_until', '>', now())
                    ->orWhereNull('valid_until');
            })
            ->whereNull('accepted_at')
            ->whereNull('rejected_at');
    }

    public function projects()
    {
        return $this
            ->belongsToMany(Project::class)
            ->withTimestamps()
            ->withPivot('role');
    }

    public function ownedProjects()
    {
        return $this->projects()->wherePivot('role', ProjectUserRoles::OWNER->value);
    }

    public function notOwnedProjects()
    {
        return $this->projects()->wherePivot('role', '!=', ProjectUserRoles::OWNER->value);
    }

    public function projectNotes()
    {
        return $this->hasMany(ProjectNote::class, 'author_id');
    }

    public function projectEditedNotes()
    {
        return $this->hasMany(ProjectNote::class, 'last_editor_id');
    }

    public function subProjects()
    {
        return $this->hasMany(SubProject::class, 'creator_id');
    }
}
