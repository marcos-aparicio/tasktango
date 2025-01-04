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

class BasicTaskSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_cant_search_other_user_labels(): void
    {
        $otherUser = User::factory()->create();
        $otherUserLabel = $otherUser->labels()->create(['name' => 'Other User Label']);
        $otherUserTask = $otherUser->tasks()->create([
            'name' => 'Other User Task',
            'description' => 'This is a test task',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'priority' => TaskPriorities::P1->value,
            'frequency' => TaskFrequencies::DAILY->value,
        ]);
        $otherUserTask->labels()->attach($otherUserLabel);

        $user = User::factory()->create();
        $userLabel = $user->labels()->create(['name' => 'User Label']);
        $userTask = $user->tasks()->create([
            'name' => 'User Task',
            'description' => 'This is a test task',
            'due_date' => now()->addDays(4)->format('Y-m-d'),
            'priority' => TaskPriorities::P1->value,
            'frequency' => TaskFrequencies::DAILY->value,
        ]);
        $userTask->labels()->attach($userLabel);

        $this->actingAs($user);
        $component = Volt::test('segments.search')
            ->call('search')
            ->assertSee($userTask->name)
            ->assertSee($userLabel->name)
            ->assertDontSee($otherUserLabel->name)
            ->assertDontSee($otherUserTask->name);
    }

    public function test_cant_search_other_user_labels_when_setting_them(): void
    {
        $otherUser = User::factory()->create();
        $otherUserLabel = $otherUser->labels()->create(['name' => 'Other User Label']);
        $otherUserTask = $otherUser->tasks()->create(['name' => 'Other User Task']);
        $otherUserTask->labels()->attach($otherUserLabel);

        $user = User::factory()->create();
        $userLabel = $user->labels()->create(['name' => 'User Label']);
        $userTask = $user->tasks()->create(['name' => 'User Task']);
        $userTask->labels()->attach($userLabel);

        $this->actingAs($user);
        $component = Volt::test('segments.search')
            ->set('selected_individual_labels', [$otherUserLabel->id, $userLabel->id])
            ->call('search')
            ->assertHasErrors();
    }

    public function test_user_cant_see_other_user_project_task(): void
    {
        [$user, $otherUser] = User::factory()->count(2)->create();
        $otherUserProject = $otherUser->projects()->create(['name' => 'Other User Project']);
        $otherUserTask = $otherUser->tasks()->create(['name' => 'a random name']);
        $otherUserTask->project()->associate($otherUserProject);
        $otherUserTask->save();

        $this->assertDatabaseHas('tasks', ['name' => 'a random name', 'project_id' => $otherUserProject->id]);

        $this->actingAs($user);
        $component = Volt::test('segments.search')
            ->set('projectID', $otherUserProject->id)
            ->call('search')
            ->assertHasErrors()
            ->assertDontSee($otherUserTask->name);
    }

    public function test_label_filters_work_correctly(): void
    {
        $user = User::factory()->create();
        $userLabel1 = $user->labels()->create(['name' => 'User Label']);
        $userLabel2 = $user->labels()->create(['name' => 'User Label 2']);
        $tasks1 = Task::factory()
            ->count(5)
            ->for($user, 'creator')
            ->hasAttached([$userLabel1])
            ->create();
        $tasks2 = Task::factory()
            ->count(5)
            ->for($user, 'creator')
            ->hasAttached([$userLabel2])
            ->create();

        $this->actingAs($user);
        $component = Volt::test('segments.search')
            ->set('selected_individual_labels', [$userLabel1->id])
            ->call('search');
        for ($i = 0; $i < 5; $i++) {
            $component->assertSee($tasks1[$i]->name);
            $component->assertDontSee($tasks2[$i]->name);
        }
    }
}
