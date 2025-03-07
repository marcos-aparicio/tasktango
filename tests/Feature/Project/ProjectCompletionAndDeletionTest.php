<?php

namespace Tests\Feature\Project;

use App\Enums\TaskStatuses;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProjectCompletionAndDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_delete_project()
    {
        $this->seed();
        $project = Project::first();
        $owner = $project->owner;
        $this->actingAs($owner);
        $comp = Volt::test('project.modal-delete-project', ['project' => $project])
            ->assertSee($project->name)
            ->call('deleteProject', $project->id);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('tasks', ['project_id' => $project->id]);
    }

    public function test_project_owner_can_complete_project()
    {
        $this->seed();
        $project = Project::first();
        $owner = $project->owner;
        $this->actingAs($owner);
        $comp = Volt::test('project.modal-complete-project', ['project' => $project])
            ->assertSee($project->name)
            ->call('completeProject', $project->id);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => TaskStatuses::COMPLETED]);
    }

    public function test_project_other_user_not_owner_cant_complete_project()
    {
        $this->seed();
        $project = Project::first();
        $otherUser = $project->users->where('id', '!=', $project->owner->id)->first();
        $this->actingAs($otherUser);
        $comp = Volt::test('project.modal-complete-project', ['project' => $project])
            ->assertSee($project->name)
            ->call('completeProject', $project->id);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => TaskStatuses::PENDING]);
    }

    public function test_project_other_user_not_owner_cant_delete_project()
    {
        $this->seed();
        $project = Project::first();
        $otherUser = $project->users->where('id', '!=', $project->owner->id)->first();
        $this->actingAs($otherUser);
        $comp = Volt::test('project.modal-delete-project', ['project' => $project])
            ->assertSee($project->name)
            ->call('deleteProject', $project->id);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => TaskStatuses::PENDING]);
        $this->assertDatabaseHas('tasks', ['project_id' => $project->id]);
    }
}
