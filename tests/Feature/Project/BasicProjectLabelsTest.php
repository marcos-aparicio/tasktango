<?php

namespace Tests\Feature\Project;

use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BasicProjectLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cant_access_non_invited_project(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $project1 = Project::factory()
            ->hasAttached($user1, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->create([
                'name' => 'Project 1', 'description' => 'This is a test project'
            ]);
        $project2 = Project::factory()
            ->hasAttached($user2, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->hasAttached($user1, ['role' => \App\Enums\ProjectUserRoles::COLLABORATOR])
            ->create(['name' => 'Project 2', 'description' => 'This is a test project 2']);

        $routes = ['project.labels', 'project.show', 'project.today', 'project.calendar', 'project.members'];
        foreach ($routes as $route)
            $response = $this
                ->actingAs($user2)
                ->get(route($route, $project1))
                ->assertStatus(403);
    }

    public function test_user_cant_add_other_project_labels_into_task_from_another_project(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $project1 = Project::factory()
            ->hasAttached($user1, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->hasAttached($user2, ['role' => \App\Enums\ProjectUserRoles::COLLABORATOR])
            ->create([
                'name' => 'Project 1', 'description' => 'This is a test project'
            ]);
        $project2 = Project::factory()
            ->hasAttached($user2, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->hasAttached($user1, ['role' => \App\Enums\ProjectUserRoles::COLLABORATOR])
            ->create(['name' => 'Project 2', 'description' => 'This is a test project 2']);

        $labelFromProject1 = new Label(['name' => 'label-prj1']);
        $labelFromProject1->project()->associate($project1);
        $labelFromProject1->user()->associate($user1);
        $labelFromProject1->save();

        $labelFromProject2 = new Label(['name' => 'label-prj2']);
        $labelFromProject2->project()->associate($project2);
        $labelFromProject2->user()->associate($user1);
        $labelFromProject2->save();

        $this->actingAs($user1);
        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.project', $project2->id)
            ->set('form.selected_project_labels', [$labelFromProject1->id])
            ->call('createTask')
            ->assertHasErrors('form.selected_project_labels.*');

        $this->assertDatabaseMissing('tasks', [
            'name' => 'Test Task',
            'description' => 'This is a test task',
            'project_id' => $project2->id,
        ]);
    }
}
