<?php
namespace App\Traits\Models\User;

use App\Enums\ProjectUserRoles;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;

/**
 * Trait Actions for User model
 * @package App\Traits\Models\User
 */
trait Actions
{
    public function createProject(string $name, string $description): array
    {
        $project = new Project([
            'name' => $name,
            'description' => $description
        ]);
        $this->projects()->save($project, ['role' => ProjectUserRoles::OWNER]);
        return ['success' => true, 'project' => $project];
    }

    /**
     * Accepts the following invitation and therefore joins to the project
     * that invitation is related to.
     */
    public function processInvitation(ProjectInvitation $projectInvitation, bool $acceptInvite): void
    {
        if ($acceptInvite) {
            $projectInvitation->project->users()->attach($projectInvitation->invitee, ['role' => ProjectUserRoles::COLLABORATOR]);
            // auditing
            $projectInvitation->project->auditEvent = "created,user-role,{$projectInvitation->invitee->id}";
            $projectInvitation->project->isCustomEvent = true;
            Event::dispatch(AuditCustom::class, [$projectInvitation->project]);
        }
        $projectInvitation->delete();
    }

    public function inviteUserToProject(User $invitee, Project $project, \DateTime|null $expiration_date): array
    {
        $invitation = new ProjectInvitation();
        $invitation->valid_until = $expiration_date;
        $invitation->project()->associate($project);
        $invitation->invitee()->associate($invitee);
        $invitation->inviter()->associate($this);
        $invitation->save();
        // auditing
        $project->auditEvent = "created,invitation,{$invitation->id}";
        $project->auditCustomOld = ['valid_until' => null, 'project_id' => null, 'invitee_id' => null, 'inviter_id' => null];
        $project->auditCustomNew = [
            'valid_until' => $expiration_date,
            'project_id' => $project->id,
            'invitee_id' => $invitee->id,
            'inviter_id' => $this->id
        ];
        $project->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$project]);
        return ['success' => true, 'invitation' => $invitation];
    }

    public function isSuperAdmin()
    {
        return $this->is_super_admin;
    }
}
