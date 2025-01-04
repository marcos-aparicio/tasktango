<?php

namespace App\Policies;

use App\Enums\TaskStatuses;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->id === $task->creator_user_id)
            return true;
        $projectFromTask = $task->project;
        if ($projectFromTask === null)
            return false;
        return $projectFromTask->users->contains($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        if (isset($task->project) && $task->project->status == TaskStatuses::COMPLETED)
            return false;

        if ($user->id === $task->creator_user_id)
            return true;
        if ($user->id === $task->assignee_user_id)
            return true;
        $projectFromTask = $task->project;
        if ($projectFromTask === null)
            return false;
        return $projectFromTask->users->contains($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): Response
    {
        if (isset($task->project) && $task->project->status == TaskStatuses::COMPLETED)
            return Response::deny('Project is completed');

        if (isset($task->project) && $task->project->owner->id === $user->id)
            return Response::allow();
        if ($user->id !== $task->creator_user_id)
            return Response::deny('You are not the creator of this task');
        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        //
    }
}
