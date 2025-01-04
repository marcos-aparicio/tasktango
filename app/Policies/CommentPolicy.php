<?php

namespace App\Policies;

use App\Enums\TaskStatuses;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Task $task): Response
    {
        if (!isset($task->project) && $task->creator->is($user))
            return Response::allow();

        if ($task->project->status == TaskStatuses::COMPLETED)
            return Response::deny('Project is completed');

        if (!$task->project->users->contains($user))
            return Response::deny('You are not a member of the project');

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): Response
    {
        if ($comment->user->is($user)) {
            return Response::allow();
        }
        return Response::deny('You are not the creator of the comment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): Response
    {
        if ($comment->user->is($user))
            return Response::allow();
        return Response::deny('You are not the creator of the comment');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        //
    }
}
