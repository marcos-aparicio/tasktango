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

class BasicInvitationSendingTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(User $user): Project
    {
        return Project::factory()
            ->hasAttached($user, ['role' => ProjectUserRoles::OWNER])
            ->create();
    }

    public function test_can_see_inviting_modal(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);

        $this
            ->actingAs($user)
            ->get(route('project.show', $project->id))
            ->assertSeeVolt('project.inviting-modal');
    }

    public function test_to_fail_inviting_your_own_user(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);

        $this
            ->actingAs($user);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $user->email)
            ->call('createInvitation')
            ->assertHasErrors(['email'])
            ->assertHasNoErrors(['selectedOption']);
    }

    public function test_to_fail_invalid_option(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->createProject($user);

        $this
            ->actingAs($user);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $otherUser->email)
            ->set('selectedOption', 5)
            ->call('createInvitation')
            ->assertHasNoErrors(['email'])
            ->assertHasErrors(['selectedOption']);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $otherUser->email)
            ->set('selectedOption', -1)
            ->call('createInvitation')
            ->assertHasNoErrors(['email'])
            ->assertHasErrors(['selectedOption']);
    }

    public function test_to_fail_inviting_not_valid_email(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);

        $this
            ->actingAs($user);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', 'not-existing-username')
            ->set('selectedOption', 3)
            ->call('createInvitation')
            ->assertHasErrors(['email'])
            ->assertHasNoErrors(['selectedOption']);
    }

    public function test_to_fail_inviting_user_already_on_project(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);
        $otherUser = User::factory()->create();
        $project->users()->attach($otherUser, ['role' => ProjectUserRoles::COLLABORATOR]);

        $this
            ->actingAs($user);
        $fakeEmail = fake()->email;
        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $otherUser->email)
            ->set('selectedOption', 3)
            ->call('createInvitation')
            ->assertHasErrors(['email']);
    }

    public function test_to_fail_inviting_not_existing_user(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);

        $this
            ->actingAs($user);

        $fakeEmail = fake()->email;
        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $fakeEmail)
            ->set('selectedOption', 3)
            ->call('createInvitation')
            ->assertHasNoErrors(['selectedOption'])
            ->assertHasErrors(['email']);
    }

    public function test_to_pass_creating_an_invitation(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);
        $otherUser = User::factory()->create();

        $this
            ->actingAs($user);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $otherUser->email)
            ->set('selectedOption', 1)
            ->call('createInvitation')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('project_invitations', [
            'project_id' => $project->id,
            'invitee_id' => $otherUser->id,
            'inviter_id' => $user->id,
            'rejected_at' => null,
            'accepted_at' => null,
        ]);
    }

    public function test_to_fail_trying_to_invite_someone_already_invited(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);
        $otherUser = User::factory()->create();

        $this
            ->actingAs($user);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $otherUser->email)
            ->set('selectedOption', 1)
            ->call('createInvitation')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('project_invitations', [
            'project_id' => $project->id,
            'invitee_id' => $otherUser->id,
            'inviter_id' => $user->id,
            'rejected_at' => null,
            'accepted_at' => null,
        ]);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $otherUser->email)
            ->set('selectedOption', 1)
            ->call('createInvitation')
            ->assertHasErrors(['email']);
    }

    public function test_collaborator_must_not_be_able_to_add_invitation(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);
        $otherUser = User::factory()->create();

        $toInviteUser = User::factory()->create();
        $project->users()->attach($otherUser, ['role' => ProjectUserRoles::COLLABORATOR]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
            'role' => ProjectUserRoles::COLLABORATOR
        ]);

        $this
            ->actingAs($otherUser);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $toInviteUser->email)
            ->set('selectedOption', 1)
            ->call('createInvitation')
            ->assertHasErrors();

        $this->assertDatabaseMissing('project_invitations', [
            'project_id' => $project->id,
            'invitee_id' => $toInviteUser->id,
            'inviter_id' => $otherUser->id,
            'rejected_at' => null,
            'accepted_at' => null,
        ]);
    }

    public function test_manager_must_be_able_to_add_invitations(): void
    {
        $user = User::factory()->create();
        $project = $this->createProject($user);
        $manager = User::factory()->create();

        $toInviteUser = User::factory()->create();
        $project->users()->attach($manager, ['role' => ProjectUserRoles::MANAGER]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $manager->id,
            'role' => ProjectUserRoles::MANAGER
        ]);

        $this
            ->actingAs($manager);

        $component = Volt::test('project.inviting-modal')
            ->call('showModal', $project)
            ->set('email', $toInviteUser->email)
            ->set('selectedOption', 1)
            ->call('createInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_invitations', [
            'project_id' => $project->id,
            'invitee_id' => $toInviteUser->id,
            'inviter_id' => $manager->id,
            'rejected_at' => null,
            'accepted_at' => null,
        ]);
    }
}
