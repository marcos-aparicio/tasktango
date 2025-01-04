<?php

namespace Tests\Feature\Main;

use App\Enums\TaskFrequencies;
use App\Enums\TaskPriorities;
use App\Enums\TaskStatuses;
use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BasicTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_see_add_test_button(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->followingRedirects()->get(route('index'));

        $response
            ->assertOk()
            ->assertSeeVolt('task.add-task-card');
    }

    public function test_can_add_task(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.priority', TaskPriorities::P1->value)
            ->set('form.frequency', TaskFrequencies::DAILY->value)
            ->call('createTask')
            ->assertDispatched('task-created');

        $component
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'description' => 'This is a test task',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'priority' => TaskPriorities::P1->value,
            'frequency' => TaskFrequencies::DAILY->value,
        ]);
    }

    public function test_cant_add_task_with_labels_from_other_users(): void
    {
        $preUser = User::factory()->create();
        $otherUserLabel = $preUser->labels()->create([
            'name' => 'Other User Label',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.priority', TaskPriorities::P1->value)
            ->set('form.frequency', TaskFrequencies::DAILY->value)
            ->set('form.selected_individual_labels', [$otherUserLabel->id])
            ->call('createTask')
            ->assertHasErrors('form.selected_individual_labels.*');
    }

    public function test_cant_add_task_with_unexisting_labels(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $maxLabelId = Label::max('id') + 1;
        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.priority', TaskPriorities::P1->value)
            ->set('form.frequency', TaskFrequencies::DAILY->value)
            ->set('form.selected_individual_labels', [$maxLabelId, $maxLabelId + 1])
            ->call('createTask')
            ->assertHasErrors('form.selected_individual_labels.*');
    }

    public function test_task_description_too_long(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $name = fake()->sentence();
        $description = str_repeat('a', config('constants.tasks.max_description_length') + 1);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', $name)
            ->set('form.description', $description)
            ->call('createTask');

        $component
            ->assertHasErrors('form.description');
    }

    public function test_task_name_too_long(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $name = str_repeat('a', config('constants.tasks.max_name_length') + 1);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', $name)
            ->call('createTask');
        $component
            ->assertHasErrors('form.name');
    }

    public function test_task_should_not_allow_past_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->subDays(4)->format('Y-m-d'))
            ->set('form.priority', TaskPriorities::P1->value)
            ->set('form.frequency', TaskFrequencies::DAILY->value)
            ->call('createTask')
            ->assertHasErrors('form.due_date');
    }

    public function test_task_should_allow_empty_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.priority', TaskPriorities::P1->value)
            ->call('createTask')
            ->assertDispatched('task-created');

        $component
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'due_date' => null,
            'description' => 'This is a test task',
            'priority' => TaskPriorities::P1->value,
        ]);
    }

    public function test_task_should_allow_empty_priority(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.frequency', TaskFrequencies::DAILY->value)
            ->call('createTask')
            ->assertDispatched('task-created');

        $component
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'description' => 'This is a test task',
            'priority' => TaskPriorities::P5->value,
            'frequency' => TaskFrequencies::DAILY->value,
        ]);
    }

    public function test_task_should_allow_empty_frequency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('task.add-task-card')
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.priority', TaskPriorities::P1->value)
            ->call('createTask')
            ->assertDispatched('task-created');

        $component
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'description' => 'This is a test task',
            'priority' => TaskPriorities::P1->value,
            'frequency' => TaskFrequencies::NONE->value,
        ]);
    }

    public function test_task_creates_labels_and_relationships_correctly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // testing inside of add card component there is the user-labels component
        $component = Volt::test('task.add-task-card')
            ->assertSeeVolt('task.subcomps.user-labels');

        $labelComponent = Volt::test('task.subcomps.user-labels')
            ->set('openLabelCreate', true)
            ->set('newLabelName', 'testlabel')
            ->call('createIndividualLabel');

        // assert label was added correctly
        $this->assertDatabaseHas('labels', [
            'name' => 'testlabel',
            'user_id' => $user->id,
        ]);
        $label = Label::where('name', 'testlabel')
            ->where('user_id', $user->id)
            ->first();

        // create task
        $component
            ->set('form.name', 'Test Task')
            ->set('form.description', 'This is a test task')
            ->set('form.due_date', now()->addDays(4)->format('Y-m-d'))
            ->set('form.priority', TaskPriorities::P1->value)
            ->set('form.selected_individual_labels', [$label->id])
            ->call('createTask')
            ->assertDispatched('task-created');

        $component
            ->assertHasNoErrors();

        // assert task was added correctly
        $this->assertDatabaseHas('tasks', [
            'name' => 'Test Task',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'description' => 'This is a test task',
            'priority' => TaskPriorities::P1->value,
            'frequency' => TaskFrequencies::NONE->value,
            'creator_user_id' => $user->id,
        ]);

        $task = Task::where('name', 'Test Task')
            ->where('due_date', now()->addDays(4)->format('Y-m-d'))
            ->where('description', 'This is a test task')
            ->where('priority', TaskPriorities::P1->value)
            ->where('frequency', TaskFrequencies::NONE->value)
            ->where('creator_user_id', $user->id)
            ->first();

        // assert many to many works
        $this->assertDatabaseHas('label_task', [
            'label_id' => $label->id,
            'task_id' => $task->id,
        ]);

        // testing the label shows in the labels page
        $this->get(route('labels'))->assertSee($label->name);
        $this->get(route('label', ['label' => $label->id]))->assertSee('Test Task');
    }
}
