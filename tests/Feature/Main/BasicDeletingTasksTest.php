<?php

namespace Tests\Feature\Main;

use App\Enums\TaskStatuses;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BasicDeletingTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_see_task_delete_modal(): void
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
            ->assertSeeVolt('task.modal-delete-task');
    }

    public function test_to_fail_trying_to_delete_other_user_tasks(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // is important to create the task before the inbox page is loaded
        $task = $otherUser->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);

        $this->actingAs($user);
        $component = Volt::test('task.modal-delete-task')
            ->set('task', $task)
            ->call('deleteTask')
            ->assertDispatched('task-delete-error');
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'status' => TaskStatuses::PENDING->value,
            'creator_user_id' => $otherUser->id,
        ]);
    }

    public function test_to_fail_trying_to_access_delete_modal_with_other_user_task_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // is important to create the task before the inbox page is loaded
        $task = $otherUser->tasks()->create([
            'name' => 'Task 1',
            'description' => 'Task 1 description',
        ]);

        $this->actingAs($user);
        $component = Volt::test('task.modal-delete-task')
            ->call('openModal', $task->id)
            ->assertDispatched('task-view-error');
    }

    public function test_can_delete_task_from_delete_task_modal(): void
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

        $component = Volt::test('task.modal-delete-task')
            ->call('openModal', $task->id)
            ->assertSee('Task 1')
            ->call('deleteTask')
            ->assertDispatched('task-deleted');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'status' => TaskStatuses::DELETED,
            'creator_user_id' => $user->id,
        ]);
    }

    public function test_cant_delete_nothing(): void
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

        $component = Volt::test('task.modal-delete-task')
            ->call('deleteTask')
            ->assertDispatched('task-delete-error');
    }

    public function test_subtasks_get_deleted_too(): void
    {
        $user = User::factory()->create();
        $rootTask = Task::factory()->for($user, 'creator')->create([
            'name' => 'Root Task',
        ]);
        $childrenTasks = Task::factory()
            ->count(3)
            ->for($user, 'creator')
            ->for($rootTask, 'taskParent')
            ->create();
        $grandparentTasks = collect();
        $childrenTasks->each(function ($childrenTask) use ($grandparentTasks, $user) {
            $grandparentTasks->push(
                Task::factory()
                    ->count(3)
                    ->for($user, 'creator')
                    ->for($childrenTask, 'taskParent')
                    ->create()
            );
        });

        $grandparentTasks = $grandparentTasks->flatten();

        $this->assertDatabaseCount('tasks', $grandparentTasks->count() + $childrenTasks->count() + 1);

        $previous = $this->actingAs($user);

        $component = Volt::test('task.modal-delete-task')
            ->call('openModal', $rootTask->id)
            ->assertSee($rootTask->name)
            ->call('deleteTask')
            ->assertDispatched('task-deleted');

        $deletedTasks = Task::query()
            ->where('status', TaskStatuses::DELETED)
            ->get();
        $this->assertCount($grandparentTasks->count() + $childrenTasks->count() + 1, $deletedTasks);
    }
}
