<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectUserRoles;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MemberRemovalTest extends TestCase
{
    use RefreshDatabase;

    private function seeding(): array
    {
        $user = User::factory()->create();
        $project = Project::factory()
            ->hasAttached($user, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->create();
        $project->users()->attach($user, ['role' => ProjectUserRoles::OWNER]);
        $manager = User::factory()->create();
        $collaborator = User::factory()->create();
        $project->users()->attach($manager, ['role' => ProjectUserRoles::MANAGER]);
        $project->users()->attach($collaborator, ['role' => ProjectUserRoles::COLLABORATOR]);

        return [$user, $project, $manager, $collaborator];
    }

    public function test_member_removed_doesnt_have_assigned_tests(): void
    {
        [$user, $project, $manager] = $this->seeding();

        // creating tasks
        $tasks = Task::factory()
            ->for($project)
            ->for($manager, 'creator')
            ->for($manager, 'assignee')
            ->count(5)
            ->create();

        // tasks where created properly
        foreach ($tasks as $task) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'assignee_user_id' => $manager->id,
                'project_id' => $project->id,
            ]);
        }

        $this->actingAs($user);
        $component = Volt::test('project.pages.members', ['project' => $project])
            ->call('removeUserFromProject', $manager->id);

        // testing user removal
        $this->assertDatabaseMissing('project_user', [
            'user_id' => $manager->id,
            'project_id' => $project->id,
        ]);

        // testing tasks reassignment
        foreach ($tasks as $task) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'assignee_user_id' => null,
                'project_id' => $project->id,
            ]);
            $taskModal = Volt::test('task.modal')
                ->call('showModal', $task->id)
                ->assertSee('Created by Previous Member');
            $activityModal = Volt::test('project.task-activity-modal')
                ->call('showModal', $task->id)
                ->assertDontSee($manager->username);
            // ->assertSee('Previous Member');
        }
    }

    public function test_comments_from_deleted_user_shown_as_deleted(): void
    {
        [$user, $project, $manager, $collaborator] = $this->seeding();
        $tasks = Task::factory()
            ->for($project)
            ->for($user, 'creator')
            ->has(Comment::factory()
                ->for($collaborator)
                ->count(5))
            ->count(5)
            ->create();

        $this->actingAs($user);
        $component = Volt::test('project.pages.members', ['project' => $project])
            ->call('removeUserFromProject', $collaborator->id)
            ->assertHasNoErrors();

        foreach ($tasks as $task) {
            $taskModal = Volt::test('task.modal')
                ->call('showModal', $task->id);

            foreach ($task->comments as $comment)
                $taskModal
                    ->assertSee($comment->content)
                    ->assertDontSee($comment->user->username)
                    ->assertSee('Previous Member');
        }
    }

    // TODO: test task activity modal in laravel dusk
    public function test_note_author_is_not_shown(): void
    {
        [$owner, $project, $manager, $collaborator] = $this->seeding();

        $notes = ProjectNote::factory()
            ->for($manager, 'author')
            ->for($project)
            ->count(3)
            ->create();

        $this->actingAs($owner);
        $component = Volt::test('project.pages.members', ['project' => $project])
            ->call('removeUserFromProject', $manager->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('project_user', [
            'user_id' => $manager->id,
            'project_id' => $project->id,
        ]);

        $notesComponent = Volt::test('project.pages.notes', ['project' => $project]);
        $notesComponent->assertSee('Previous Member');
        $notesComponent->assertDontSee($manager->username);

        foreach ($notes as $note)
            $individualNote = Volt::test('project.pages.note',
                ['project' => $project, 'note' => $note])
                ->assertSee('Previous Member')
                ->assertDontSee($manager->username);
    }
}
