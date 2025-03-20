<?php

namespace Tests\Main;

use App\Enums\TaskFrequencies;
use App\Enums\TaskPriorities;
use App\Enums\TaskStatuses;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BasicTaskModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_see_task_modal(): void
    {
        $user = User::factory()->create();

        // is important to create the task before the inbox page is loaded
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);
        $this
            ->actingAs($user)
            ->get(route('inbox'))
            ->assertSee('Task 1')
            ->assertSeeVolt('task.card');

        $component = Volt::test('task.card', ['task' => $task])
            ->assertSee($task->name)
            ->call('openModal')
            ->assertDispatched('open-task-modal');
    }

    public function test_to_fail_trying_to_see_other_user_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // is important to create the task before the inbox page is loaded
        $task = $otherUser->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);

        $this->actingAs($user);
        $component = Volt::test('task.modal')
            ->call('showModal', $task->id)
            ->assertDispatched('task-view-error');
    }

    public function test_can_create_task_from_task_modal(): void
    {
        $user = User::factory()->create();

        // is important to create the task before the inbox page is loaded
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);
        $previous = $this
            ->actingAs($user)
            ->get(route('inbox'))
            ->assertSee('Task 1')
            ->assertSeeVolt('task.card');

        $component = Volt::test('task.modal')
            ->call('showModal', -1, false, [])
            ->set('form.name', 'Task 2')
            ->set('form.description', 'Task 2 description')
            ->set('form.frequency', TaskFrequencies::NONE->value)
            ->assertSet('form.name', 'Task 2')
            ->call('updateOrCreateTask')
            ->assertDispatched('task-created')
            ->assertNotSet('form.name', 'Task 2');

        $this->assertDatabaseHas('tasks', [
            'name' => 'Task 2',
            'description' => 'Task 2 description',
            'creator_user_id' => $user->id,
        ]);
    }

    public function test_cant_create_recurrent_task_from_modal_without_due_date(): void
    {
        $user = User::factory()->create();

        // is important to create the task before the inbox page is loaded
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);
        $previous = $this
            ->actingAs($user)
            ->get(route('inbox'))
            ->assertSee('Task 1')
            ->assertSeeVolt('task.card');

        $component = Volt::test('task.modal')
            ->call('showModal', -1, false, [])
            ->set('form.name', 'Task 2')
            ->set('form.description', 'Task 2 description')
            ->set('form.frequency', TaskFrequencies::DAILY->value)
            ->assertSet('form.name', 'Task 2')
            ->call('updateOrCreateTask')
            ->assertHasErrors('form.due_date');

        $this->assertDatabaseMissing('tasks', [
            'name' => 'Task 2',
            'description' => 'Task 2 description',
            'creator_user_id' => $user->id,
        ]);
    }

    public function test_task_can_be_updated_through_modal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);

        $component = Volt::test('task.modal')
            ->call('showModal', task_id: $task->id)
            ->assertHasNoErrors()
            ->assertSet('openTaskModal', true)
            ->assertSet('form.name', 'Task 1')
            ->set('form.name', 'Task 1 Updated')
            ->set('form.description', 'Task 1 description updated')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.priority', TaskPriorities::P1->value)
            ->set('form.frequency', TaskFrequencies::DAILY->value)
            ->call('updateOrCreateTask');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Task 1 Updated',
            'project_id' => null,
            'description' => 'Task 1 description updated',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'priority' => (string) TaskPriorities::P1->value,
            'frequency' => (string) TaskFrequencies::DAILY->value,
        ]);
    }

    public function test_task_can_change_project_to_other_project_through_modal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $otherProject = $user->ownedProjects()->create([
            'name' => 'Project 2',
            'description' => 'Project 2 description',
        ]);
        $project = $user->ownedProjects()->create([
            'name' => 'Project 1',
            'description' => 'Project 1 description',
        ]);
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);
        $task->project()->associate($project->id);

        $component = Volt::test('task.modal')
            ->call('showModal', task_id: $task->id)
            ->assertHasNoErrors()
            ->assertSet('openTaskModal', true)
            ->assertSet('form.name', 'Task 1')
            ->set('form.project', $otherProject->id)
            ->call('updateOrCreateTask');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Task 1',
            'project_id' => $otherProject->id,
        ]);
    }

    public function test_task_can_change_no_project_to_project_through_modal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $project = $user->ownedProjects()->create([
            'name' => 'Project 1',
            'description' => 'Project 1 description',
        ]);
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);

        $component = Volt::test('task.modal')
            ->call('showModal', task_id: $task->id)
            ->assertHasNoErrors()
            ->assertSet('openTaskModal', true)
            ->assertSet('form.name', 'Task 1')
            ->set('form.project', $project->id)
            ->call('updateOrCreateTask');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Task 1',
            'project_id' => $project->id,
        ]);
    }

    public function test_task_can_change_project_to_no_project_through_modal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $project = $user->ownedProjects()->create([
            'name' => 'Project 1',
            'description' => 'Project 1 description',
        ]);
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);
        $task->project()->associate($project->id);

        $component = Volt::test('task.modal')
            ->call('showModal', task_id: $task->id)
            ->assertHasNoErrors()
            ->assertSet('openTaskModal', true)
            ->assertSet('form.name', 'Task 1')
            ->set('form.project', null)
            ->call('updateOrCreateTask');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Task 1',
            'project_id' => null,
        ]);
    }

    public function test_can_be_completed_and_uncompleted_via_modal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);

        $component = Volt::test('task.modal')
            ->call('showModal', task_id: $task->id)
            ->assertHasNoErrors()
            ->assertSet('openTaskModal', true)
            ->assertSet('form.name', 'Task 1')
            ->call('completeTask', true)
            ->assertDispatched('task-completed');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatuses::COMPLETED->value,
        ]);

        $component
            ->call('completeTask', false);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatuses::PENDING->value,
        ]);
    }
}
