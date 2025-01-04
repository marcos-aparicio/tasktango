<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectUserRoles;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BasicProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $component = Volt::test('project.create-modal')
            ->set('name', 'Test Project')
            ->set('description', 'This is a test project')
            ->call('createProject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'description' => 'This is a test project',
        ]);
        $this->assertDatabaseHas('project_user', [
            'user_id' => $user->id,
            'role' => ProjectUserRoles::OWNER,
            'project_id' => Project::where('name', 'Test Project')->first()->id,
        ]);
    }

    public function test_user_can_create_project_without_desc(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $component = Volt::test('project.create-modal')
            ->set('name', 'Test Project')
            ->call('createProject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'description' => null,
        ]);
        $this->assertDatabaseHas('project_user', [
            'user_id' => $user->id,
            'role' => ProjectUserRoles::OWNER,
            'project_id' => Project::where('name', 'Test Project')->first()->id,
        ]);
    }

    public function test_user_cant_create_project_bcs_of_description(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $description = str_repeat('a', 513);
        $component = Volt::test('project.create-modal')
            ->set('name', 'project name')
            ->set('description', $description)
            ->call('createProject')
            ->assertHasErrors('description');
    }

    public function test_user_cant_create_project_bcs_of_name(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $name = str_repeat('a', 256);
        $component = Volt::test('project.create-modal')
            ->set('name', $name)
            ->set('description', 'This is a test project')
            ->call('createProject')
            ->assertHasErrors('name');
    }

    public function test_user_cant_enter_tasks_to_other_non_invited_project(): void
    {
        $preUser = User::factory()->create();
        $this->actingAs($preUser);
        $name = fake()->sentence(1);
        $component = Volt::test('project.create-modal')
            ->set('name', $name)
            ->set('description', 'This is a test project')
            ->call('createProject');
        $id = Project::where('name', $name)->first()->id;

        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.project', $id)
            ->call('createTask')
            ->assertHasErrors('form.project');
    }

    public function test_user_can_enter_task_to_own_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $name = fake()->sentence(1);
        $component = Volt::test('project.create-modal')
            ->set('name', $name)
            ->set('description', 'This is a test project')
            ->call('createProject');
        $id = Project::where('name', $name)->first()->id;

        $this->actingAs($user);
        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.project', $id)
            ->call('createTask')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'description' => 'This is a test task',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'project_id' => $id,
            'creator_user_id' => $user->id,
        ]);
    }
}
