<?php
namespace App\Traits\Models\Task;

use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\SubProject;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait Relations
{
    /**
     * Define a many-to-one relationship with the User model for the creator.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function creatorUsername(): Attribute
    {
        return new Attribute(
            get: function () {
                if (isset($this->project) && !$this->project->users->contains($this->creator))
                    return 'Previous Member';
                return $this->creator->username;
            }
        );
    }

    /**
     * Define a many-to-one relationship with the User model for the assignee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    /**
     * Define a many-to-one relationship with the Task model for the parent task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function taskParent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id', 'id');
    }

    /**
     * Define a one to many relationship with the Task model for possible sub-tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subTasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id', 'id');
    }

    /**
     * Defines a many-to-many relationship with the Label model for the labels associated with the task.
     * The labels that belong to the task
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function labels()
    {
        return $this->belongsToMany(Label::class);
    }

    public function labelsIndividual()
    {
        return $this
            ->belongsToMany(Label::class)
            ->whereNull('project_id')
            ->where('user_id', auth()->id());
    }

    public function labelsProject()
    {
        return $this
            ->belongsToMany(Label::class)
            ->whereNotNull('project_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function subproject()
    {
        return $this->belongsTo(SubProject::class, 'sub_project_id');
    }
}
