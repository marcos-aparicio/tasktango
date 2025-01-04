<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectUserRoles;
use App\Enums\TaskStatuses;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BasicInvitationCancelingTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(User $user): Project
    {
        return Project::factory()
            ->hasAttached($user, ['role' => ProjectUserRoles::OWNER])
            ->create();
    }

    public function test_owner_can_cancel_invitation(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = $this->createProject($user);
        $invitation = $user->inviteUserToProject($anotherUser, $project, now()->addDays(1))['invitation'];

        $this->assertDatabaseHas('project_invitations', [
            'inviter_id' => $user->id,
            'invitee_id' => $anotherUser->id,
            'project_id' => $project->id,
            'valid_until' => now()->addDays(1),
        ]);

        $this
            ->actingAs($user);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->assertSee('Pending Invitations')
            ->assertSee($anotherUser->username)
            ->call('cancelInvitation', $invitation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('project_invitations', [
            'inviter_id' => $user->id,
            'invitee_id' => $anotherUser->id,
            'project_id' => $project->id,
            'id' => $invitation->id,
        ]);
    }

    public function test_manager_can_cancel_invitation(): void
    {
        // creating users
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $user = User::factory()->create();

        // creating project and setting up roles and invitation
        $project = $this->createProject($owner);
        $project->users()->attach($manager, ['role' => ProjectUserRoles::MANAGER]);
        $invitation = $owner->inviteUserToProject($user, $project, now()->addDays(1))['invitation'];

        $this->assertDatabaseHas('project_invitations', [
            'inviter_id' => $owner->id,
            'invitee_id' => $user->id,
            'project_id' => $project->id,
            'id' => $invitation->id,
        ]);

        $this
            ->actingAs($manager);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->assertSee('Pending Invitations')
            ->assertSee($user->username)
            ->call('cancelInvitation', $invitation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('project_invitations', [
            'inviter_id' => $manager->id,
            'invitee_id' => $user->id,
            'project_id' => $project->id,
            'id' => $invitation->id,
        ]);
    }

    public function test_collaborator_cant_cancel_invitation(): void
    {
        // creating users
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $user = User::factory()->create();

        // creating project and setting up roles and invitation
        $project = $this->createProject($owner);
        $project->users()->attach($collaborator, ['role' => ProjectUserRoles::COLLABORATOR]);
        $invitation = $owner->inviteUserToProject($user, $project, now()->addDays(1))['invitation'];

        $this->assertDatabaseHas('project_invitations', [
            'inviter_id' => $owner->id,
            'invitee_id' => $user->id,
            'project_id' => $project->id,
            'id' => $invitation->id,
        ]);

        $this
            ->actingAs($collaborator);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->assertSee('Pending Invitations')
            ->assertSee($user->username)
            ->call('cancelInvitation', $invitation->id)
            ->assertHasErrors();

        $this->assertDatabaseHas('project_invitations', [
            'inviter_id' => $owner->id,
            'invitee_id' => $user->id,
            'project_id' => $project->id,
            'id' => $invitation->id,
        ]);
    }

    public function test_random_user_cant_decline_invitation(): void
    {
        // creating users
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $otherOwner = User::factory()->create();

        $project = $this->createProject($owner);
        $unrelatedProject = $this->createProject($otherOwner);

        $invitation = $owner->inviteUserToProject($invitee, $project, now()->addDays(1))['invitation'];

        $this->assertDatabaseHas('project_invitations', [
            'inviter_id' => $owner->id,
            'invitee_id' => $invitee->id,
            'project_id' => $project->id,
            'id' => $invitation->id,
        ]);

        $this
            ->actingAs($otherOwner);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->assertSee('Pending Invitations')
            ->assertSee($invitee->username)
            ->call('cancelInvitation', $invitation->id)
            ->assertHasErrors();

        $this->assertDatabaseHas('project_invitations', [
            'inviter_id' => $owner->id,
            'invitee_id' => $invitee->id,
            'project_id' => $project->id,
            'id' => $invitation->id,
        ]);
    }
}
