<?php

namespace Database\Seeders;

use App\Enums\TaskFrequencies;
use App\Enums\TaskStatuses;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first();
        $projects = \App\Models\Project::factory()
            ->count(30)
            ->sequence(fn(Sequence $sequence) =>
                [
                    'name' => 'Project ' . ($sequence->index + 1),
                    'description' => 'Description for project ' . ($sequence->index + 1)
                ])
            ->hasAttached($user, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->create();
        // generate invitations
        $users = User::query()->get()->except($user->id)->take(18);
        foreach ($users as $invitee)
            $user->inviteUserToProject($invitee, $projects[0], now()->addDays(7));

        $secondUser = User::query()->skip(1)->first();
        $projects = \App\Models\Project::factory()
            ->count(30)
            ->sequence(fn(Sequence $sequence) =>
                [
                    'name' => 'Project ' . ($sequence->index + 1),
                    'description' => 'Description for project ' . ($sequence->index + 1)
                ])
            ->hasAttached($secondUser, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->hasAttached($user, ['role' => \App\Enums\ProjectUserRoles::COLLABORATOR])
            ->create();
        $this->overdueInProjectTesting(false);
        $this->multipleMembersSeed();
        $this->multipleProjectNotesSeed();
        $this->tasksSeed();
    }

    public function overdueInProjectTesting($isOverdue = true): void
    {
        $user = User::first();
        $numberOfTasks = 1;
        $numberOfDays = 4;
        for ($i = 0; $i < $numberOfDays; $i++) {
            if ($isOverdue)
                $date = now()->subDays($i);
            else
                $date = now()->addDays($i);
            Task::factory()
                ->count($numberOfTasks)
                ->for($user->projects->first())
                ->for($user, 'creator')
                ->create([
                    'due_date' => $date,
                    'frequency' => TaskFrequencies::NONE,
                ]);

            Task::factory()
                ->count($numberOfTasks)
                ->for($user->projects->first())
                ->for($user, 'creator')
                ->create([
                    'status' => TaskStatuses::COMPLETED,
                ]);
        }
    }

    public function multipleMembersSeed(): void
    {
        $user = User::first();
        $project = $user->projects->first();

        $usersToAttach = User::query()->skip(1)->take(29)->get();  // Adjust the number to the desired count
        $attachData = [];
        $halfCount = ceil($usersToAttach->count() / 2);

        foreach ($usersToAttach as $index => $userToAttach) {
            $role = $index >= $halfCount ? \App\Enums\ProjectUserRoles::COLLABORATOR : \App\Enums\ProjectUserRoles::MANAGER;
            $attachData[$userToAttach->id] = ['role' => $role];
        }

        $project->users()->attach($attachData);
    }

    public function multipleProjectNotesSeed(): void
    {
        $user = User::first();
        $project = $user->projects->first();
        $manager = User::query()->skip(1)->first();
        ProjectNote::factory()
            ->count(10)
            ->for($project)
            ->for($manager, 'author')
            ->create();
    }

    public function tasksSeed(): void
    {
        $project = Project::first();
        $secondUser = User::query()->skip(1)->first();
        Task::factory()
            ->for($project)
            ->for($secondUser, 'creator')
            ->sequence(fn(Sequence $sequence) =>
                [
                    'name' => 'assigned ' . ($sequence->index + 1),
                    'description' => 'assigned ' . ($sequence->index + 1)
                ])
            ->for($secondUser, 'assignee')
            ->count(3)
            ->create();
    }
}
