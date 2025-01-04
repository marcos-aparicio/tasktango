<?php

namespace App\Policies;

use App\Enums\ProjectUserRoles;
use App\Enums\TaskStatuses;
use App\Models\NoteAttachment;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectNotePolicy
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
    public function view(User $user, ProjectNote $projectNote): bool
    {
        $project = $projectNote->project;

        return $project->users->contains($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Project $project): Response
    {
        if (!$project->users->contains($user))
            return Response::deny();
        $projectUser = $project->users->find($user);

        if ($project->status == TaskStatuses::COMPLETED)
            return Response::deny('Project is completed');

        if ($projectUser->pivot->role == ProjectUserRoles::OWNER->value ||
                $projectUser->pivot->role == ProjectUserRoles::MANAGER->value)
            return Response::allow();

        return Response::deny('Not enough permissions');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProjectNote $projectNote, Project $project): Response
    {
        if ($projectNote->project->isNot($project))
            return Response::deny('Note does not belong to this project');

        return $this->create($user, $project);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectNote $projectNote, Project $project): Response
    {
        return $this->update($user, $projectNote, $project);
    }

    public function downloadAttachment(User $user, Project $project, NoteAttachment $attachment): Response
    {
        if (!$project->users->contains($user))
            return Response::deny('You are not a member of this project');

        if ($attachment->note->project->isNot($project))
            return Response::deny('Attachment does not belong to this project');

        if ($project->users->contains($user))
            return Response::allow();

        return Response::deny();
    }

    public function deleteAttachment(User $user, Project $project, NoteAttachment $attachment): Response
    {
        if ($project->status == TaskStatuses::COMPLETED)
            return Response::deny('Project is completed');

        return $this->downloadAttachment($user, $project, $attachment);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProjectNote $projectNote): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProjectNote $projectNote): bool
    {
        //
    }
}
