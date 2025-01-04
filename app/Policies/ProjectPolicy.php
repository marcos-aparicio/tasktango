<?php

namespace App\Policies;

use App\Enums\ProjectUserRoles;
use App\Enums\TaskStatuses;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
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
    public function view(User $user, Project $project): bool
    {
        return $project->users->contains($user);
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
    public function update(User $user, Project $project): bool
    {
        //
    }

    /**
     * Determines whether a user in the project can upgrade the role
     * from another user
     *
     * Only available for owners and managers
     * Only available upgrade is from collaborator to manager
     */
    public function upgradeOtherUserRole(User $user, Project $project, User $otherUser): bool
    {
        if ($project->status == TaskStatuses::COMPLETED)
            return false;
        if (!$project->users->contains($user) || !$project->users->contains($otherUser))
            return false;

        if ($otherUser->is($user))
            return false;

        $user = $project->users->find($user->id);
        $otherUser = $project->users->find($otherUser->id);

        if ($otherUser->pivot->role == ProjectUserRoles::OWNER->value)
            return false;

        if ($otherUser->pivot->role == ProjectUserRoles::MANAGER->value)
            return false;

        if ($user->pivot->role == ProjectUserRoles::OWNER->value || $user->pivot->role == ProjectUserRoles::MANAGER->value)
            return true;

        return false;
    }

    public function downgradeOtherUserRole(User $user, Project $project, User $otherUser): bool
    {
        if ($project->status == TaskStatuses::COMPLETED)
            return false;

        if (!$project->users->contains($user) || !$project->users->contains($otherUser))
            return false;

        if ($otherUser->is($user))
            return false;

        $user = $project->users->find($user->id);
        $otherUser = $project->users->find($otherUser->id);

        if ($otherUser->pivot->role == ProjectUserRoles::OWNER->value)
            return false;

        if ($otherUser->pivot->role == ProjectUserRoles::COLLABORATOR->value)
            return false;

        if ($user->pivot->role == ProjectUserRoles::OWNER->value || $user->pivot->role == ProjectUserRoles::MANAGER->value)
            return true;

        return false;
    }

    public function removeUserFromProject(User $user, Project $project, User $otherUser): bool
    {
        if ($project->status == TaskStatuses::COMPLETED)
            return false;
        if (!$project->users->contains($user) || !$project->users->contains($otherUser))
            return false;

        $user = $project->users->find($user->id);
        $otherUser = $project->users->find($otherUser->id);

        if ($otherUser->pivot->role == ProjectUserRoles::OWNER->value)
            return false;

        if ($user->pivot->role == ProjectUserRoles::OWNER->value)
            return true;

        return false;
    }

    public function seeUserStats(User $user, Project $project): bool
    {
        if (!$project->users->contains($user))
            return false;

        $user = $project->users->find($user->id);

        if ($user->pivot->role == ProjectUserRoles::OWNER->value || $user->pivot->role == ProjectUserRoles::MANAGER->value)
            return true;

        return false;
    }

    public function seeTaskActivity(User $user, Project $project): bool
    {
        if (!$project->users->contains($user))
            return false;
        $user = $project->users->find($user);

        if ($user->pivot->role == ProjectUserRoles::OWNER->value || $user->pivot->role == ProjectUserRoles::MANAGER->value)
            return true;

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        $projectUser = $project->users->find($user);
        if ($projectUser == null)
            return false;
        return $projectUser->pivot->role == ProjectUserRoles::OWNER->value;
    }

    public function complete(User $user, Project $project): bool
    {
        $projectUser = $project->users->find($user);
        if ($project->status == TaskStatuses::COMPLETED)
            return false;
        if ($projectUser == null)
            return false;
        return $projectUser->pivot->role == ProjectUserRoles::OWNER->value;
    }

    public function seeProjectActivity(User $user, Project $project): bool
    {
        return $project->users->contains($user) && $project->users->find($user)->pivot->role != ProjectUserRoles::COLLABORATOR->value;
    }

    public function editProject(User $user, Project $project): bool
    {
        return $project->users->contains($user) && $project->users->find($user)->pivot->role != ProjectUserRoles::COLLABORATOR->value;
    }

    public function invite(User $user, Project $project): bool
    {
        return $project->users->contains($user) && $project->users->find($user)->pivot->role != ProjectUserRoles::COLLABORATOR->value && $project->status != TaskStatuses::COMPLETED;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        //
    }
}
