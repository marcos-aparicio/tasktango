<?php

namespace App\Policies;

use App\Enums\ProjectUserRoles;
use App\Enums\TaskStatuses;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectInvitationPolicy
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
    public function view(User $user, ProjectInvitation $projectInvitation): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $inviter, User $invitee, Project $project): Response
    {
        if ($project == null)
            return Response::deny('Project not found');

        if (!$project->users->contains($inviter))
            return Response::deny('You are not a member of the project');

        if ($project->status == TaskStatuses::COMPLETED)
            return Response::deny('Project is completed');

        if ($invitee->is($inviter))
            return Response::deny('You cannot invite yourself');

        $projectUserInviter = $project->users->find($inviter);

        if ($projectUserInviter == null)
            return Response::deny('You are not a member of the project');

        if ($projectUserInviter->pivot->role == ProjectUserRoles::COLLABORATOR->value)
            return Response::deny('Not enough permissions to invite users');

        if ($project->users->contains($invitee))
            return Response::deny('User is already a member of the project');

        $alreadyInvited = $invitee->receivedProjectInvitationsValid->contains(fn($invitation) => $invitation->project->id == $project->id);
        if ($alreadyInvited)
            return Response::deny('User already has a pending invitation for this project');

        if ($projectUserInviter->pivot->role == ProjectUserRoles::OWNER->value ||
                $projectUserInviter->pivot->role == ProjectUserRoles::MANAGER->value)
            return Response::allow();

        return Response::deny("Can't invite users");
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProjectInvitation $projectInvitation): Response
    {
        if (!$projectInvitation)
            return Response::deny('Invitation not found');

        if ($user->isNot($projectInvitation->invitee))
            return Response::deny('Wrong invitation');

        if ($projectInvitation->valid_until?->isPast() ?? false)
            return Response::deny('Invitation has expired');

        if ($projectInvitation->project->owner->is($user))
            return Response::deny('You are the creator of the project');

        if ($projectInvitation->project->users->contains($user))
            return Response::deny('You are already a member of the project');

        if ($user->is($projectInvitation->invitee))
            return Response::allow();

        return Response::deny('Wrong invitation');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectInvitation $projectInvitation): Response
    {
        //
        if ($projectInvitation == null)
            return Response::deny('Invitation not found');

        if ($projectInvitation->project->status == TaskStatuses::COMPLETED)
            return Response::deny('Project is completed');

        $projectUser = $projectInvitation->project->users->find($user);
        if ($projectUser == null)
            return Response::deny('You are not a member of the project');

        if (isset($projectInvitation->valid_until) && $projectInvitation->valid_until < now())
            return Response::deny('Invitation has expired');

        if ($projectUser->pivot->role == ProjectUserRoles::COLLABORATOR->value)
            return Response::deny('Not enough permissions to delete invitations');

        if ($projectUser->pivot->role == ProjectUserRoles::OWNER->value ||
                $projectUser->pivot->role == ProjectUserRoles::MANAGER->value)
            return Response::allow();

        return Response::deny('Wrong invitation');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProjectInvitation $projectInvitation): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProjectInvitation $projectInvitation): bool
    {
        //
    }
}
