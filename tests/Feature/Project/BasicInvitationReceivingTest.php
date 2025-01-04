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

class BasicInvitationReceivingTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(User $user): Project
    {
        return Project::factory()
            ->hasAttached($user, ['role' => ProjectUserRoles::OWNER])
            ->create();
    }

    public function test_can_see_current_invitations(): void
    {
        $inviter = User::factory()->create();
        $project = $this->createProject($inviter);
        $invitee = User::factory()->create();
        $inviter->inviteUserToProject($invitee, $project, now()->addDays(1));

        $this
            ->actingAs($invitee)
            ->followingRedirects()
            ->get(route('root'))
            ->assertSeeVolt('project.invitations-modal');

        $component = Volt::test('project.invitations-modal')
            ->call('showModal')
            ->assertSee($project->name);
    }

    public function test_cant_see_expired_invitations(): void
    {
        $inviter = User::factory()->create();
        $project = $this->createProject($inviter);
        $invitee = User::factory()->create();
        $inviter->inviteUserToProject($invitee, $project, now()->subDays(2));

        $this
            ->actingAs($invitee)
            ->followingRedirects()
            ->get(route('root'))
            ->assertSeeVolt('project.invitations-modal');

        $component = Volt::test('project.invitations-modal')
            ->call('showModal')
            ->assertDontSee($project->name);
    }

    public function test_to_fail_cant_join_unexisting_project_invitation(): void
    {
        $inviter = User::factory()->create();
        $project = $this->createProject($inviter);
        $invitee = User::factory()->create();
        $inviter->inviteUserToProject($invitee, $project, now()->addDays(1));

        $this
            ->actingAs($invitee)
            ->followingRedirects()
            ->get(route('root'))
            ->assertSeeVolt('project.invitations-modal');

        $component = Volt::test('project.invitations-modal')
            ->call('showModal')
            ->assertSee($project->name)
            ->call('processInvitation', -1, true)
            ->assertHasErrors();
    }

    public function test_to_fail_cant_join_other_user_invitation(): void
    {
        $inviter = User::factory()->create();
        $project = $this->createProject($inviter);
        $invitee = User::factory()->create();
        $otherUser = User::factory()->create();
        $invitation = $inviter->inviteUserToProject($invitee, $project, now()->addDays(1))['invitation'];

        $this
            ->actingAs($otherUser)
            ->followingRedirects()
            ->get(route('root'))
            ->assertSeeVolt('project.invitations-modal');

        $component = Volt::test('project.invitations-modal')
            ->call('showModal')
            ->call('processInvitation', $invitation->id, true)
            ->assertHasErrors();
    }

    public function test_to_fail_joining_expired_invitation(): void
    {
        $inviter = User::factory()->create();
        $project = $this->createProject($inviter);
        $invitee = User::factory()->create();
        $invitation = $inviter->inviteUserToProject($invitee, $project, now()->subDays(2))['invitation'];

        $this
            ->actingAs($invitee)
            ->followingRedirects()
            ->get(route('root'))
            ->assertSeeVolt('project.invitations-modal');

        $component = Volt::test('project.invitations-modal')
            ->call('showModal')
            ->call('processInvitation', $invitation->id, true)
            ->assertHasErrors();
    }

    public function test_cant_join_project_that_user_is_already_in(): void
    {
        $inviter = User::factory()->create();
        $project = $this->createProject($inviter);
        $invitee = User::factory()->create();
        $invitee->projects()->attach($project, ['role' => ProjectUserRoles::COLLABORATOR]);
        $invitation = $inviter->inviteUserToProject($invitee, $project, now()->addDays(1))['invitation'];

        $this
            ->actingAs($invitee)
            ->followingRedirects()
            ->get(route('root'))
            ->assertSeeVolt('project.invitations-modal');

        $component = Volt::test('project.invitations-modal')
            ->call('showModal')
            ->call('processInvitation', $invitation->id, true)
            ->assertHasErrors();
    }

    public function test_can_join_project(): void
    {
        $inviter = User::factory()->create();
        $project = $this->createProject($inviter);
        $invitee = User::factory()->create();
        $invitation = $inviter->inviteUserToProject($invitee, $project, now()->addDays(1))['invitation'];

        $this
            ->actingAs($invitee)
            ->followingRedirects()
            ->get(route('root'))
            ->assertSeeVolt('project.invitations-modal');

        $component = Volt::test('project.invitations-modal')
            ->call('showModal')
            ->call('processInvitation', $invitation->id, true)
            ->assertHasNoErrors()
            ->assertRedirect(route('project.show', $project->id));
    }
}
