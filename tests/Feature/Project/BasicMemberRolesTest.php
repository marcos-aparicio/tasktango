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

class BasicMemberRolesTest extends TestCase
{
    use RefreshDatabase;

    private function prefill(User $owner): array
    {
        $project = Project::factory()
            ->hasAttached($owner, ['role' => ProjectUserRoles::OWNER])
            ->create();

        $collaboratorUsers = User::factory(4)->create();
        $project->users()->attach($collaboratorUsers, ['role' => ProjectUserRoles::COLLABORATOR]);

        $managerUsers = User::factory(3)->create();
        $project->users()->attach($managerUsers, ['role' => ProjectUserRoles::MANAGER]);

        return [$project, $collaboratorUsers, $managerUsers];
    }

    public function test_see_members_correctly(): void
    {
        $owner = User::factory()->create();
        [$project, $collaborators, $managers] = $this->prefill($owner);
        $roleName = $project->getRoleName($owner);

        $testing = $this
            ->actingAs($owner)
            ->get(route('project.members', $project->id))
            ->assertSee($owner->username)
            ->assertSee($owner->email)
            ->assertSee("User Role: $roleName");

        foreach ($collaborators as $collaboratorUser)
            $testing->assertSee($collaboratorUser->username);

        foreach ($managers as $managerUser)
            $testing->assertSee($managerUser->username);
    }

    public function test_owner_can_change_roles(): void
    {
        $owner = User::factory()->create();
        [$project, $collaborators, $managers] = $this->prefill($owner);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $collaborators->first()->id,
            'role' => ProjectUserRoles::COLLABORATOR->value,
        ]);

        $this->actingAs($owner);

        $component = Volt::test('project.pages.members', ['project' => $project])
            ->assertSee($owner->username)
            ->assertSee($owner->email)
            ->call('upgradeOtherUserRole', $collaborators->first()->id);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $collaborators->first()->id,
            'role' => ProjectUserRoles::MANAGER->value,
        ]);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $managers->first()->id,
            'role' => ProjectUserRoles::MANAGER->value,
        ]);
        $component->call('downgradeOtherUserRole', $managers->first()->id);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $managers->first()->id,
            'role' => ProjectUserRoles::COLLABORATOR->value,
        ]);
    }

    public function test_owner_cant_change_its_own_role(): void
    {
        $owner = User::factory()->create();
        [$project, $collaborators, $managers] = $this->prefill($owner);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => ProjectUserRoles::OWNER->value,
        ]);

        $this->actingAs($owner);

        $component = Volt::test('project.pages.members', ['project' => $project])
            ->assertSee($owner->username)
            ->assertSee($owner->email)
            ->call('upgradeOtherUserRole', $owner->id);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => ProjectUserRoles::OWNER->value,
        ]);
    }

    public function test_manager_cant_change_its_own_role(): void
    {
        $owner = User::factory()->create();
        [$project, $collaborators, $managers] = $this->prefill($owner);

        $some_manager = $managers->first();
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $some_manager->id,
            'role' => ProjectUserRoles::MANAGER->value,
        ]);

        $this->actingAs($some_manager);

        $component = Volt::test('project.pages.members', ['project' => $project])
            ->assertSee($some_manager->username)
            ->assertSee($some_manager->email)
            ->call('upgradeOtherUserRole', $some_manager->id);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => ProjectUserRoles::OWNER->value,
        ]);
        $component->call('downgradeOtherUserRole', $some_manager->id);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $some_manager->id,
            'role' => ProjectUserRoles::MANAGER->value,
        ]);
    }
}
