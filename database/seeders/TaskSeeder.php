<?php

namespace Database\Seeders;

use App\Enums\TaskFrequencies;
use App\Enums\TaskStatuses;
use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->subtasksTesting();
        // $this->labelTesting();
        $this->overdueTesting();
    }

    public function subtasksTesting(): void
    {
        $user = User::first();
        $depth = 2;
        $tasks_per_depth = 3;

        $createSubtasks = function ($parentTask, $currentDepth) use (&$createSubtasks, $tasks_per_depth, $user, $depth) {
            if ($currentDepth > $depth) {
                return;
            }

            for ($i = 0; $i < $tasks_per_depth; $i++) {
                $subtask = Task::factory()->for($user, 'creator')->create([
                    'parent_task_id' => $parentTask->id,
                    'frequency' => TaskFrequencies::NONE,
                ]);
                $createSubtasks($subtask, $currentDepth + 1);
            }
        };

        $task = Task::factory()->for($user, 'creator')->create([
            'name' => str_repeat('a', 23),
            'frequency' => TaskFrequencies::NONE,
            'description' => implode(' ', fake()->sentences(23)),
        ]);
        $createSubtasks($task, 1);
    }

    public function labelTesting(): void
    {
        $user = User::first();
        $numberOfTasks = 2;
        $numberOfDays = 2;
        $labels = Label::factory()->for($user)->count(30)->create();
        for ($i = 0; $i < $numberOfDays; $i++) {
            $date = now()->addDays($i);
            $randomLabels = $labels->random(random_int(1, 15));
            Task::factory()
                ->count($numberOfTasks)
                ->for($user, 'creator')
                ->hasAttached($randomLabels)
                ->create([
                    'due_date' => $date,
                    'frequency' => TaskFrequencies::NONE,
                ]);

            Task::factory()
                ->count($numberOfTasks)
                ->for($user, 'creator')
                ->hasAttached($randomLabels)
                ->create([
                    'status' => TaskStatuses::COMPLETED,
                ]);
        }
    }

    public function overdueTesting(): void
    {
        $user = User::first();
        $numberOfTasks = 5;
        $numberOfDays = 7;
        $labels = Label::factory()->for($user)->count(30)->create();
        for ($i = 0; $i < $numberOfDays; $i++) {
            $date = now()->subDays($i);
            $randomLabels = $labels->random(random_int(1, 15));
            Task::factory()
                ->count($numberOfTasks)
                ->for($user, 'creator')
                ->hasAttached($randomLabels)
                ->create([
                    'due_date' => $date,
                    'frequency' => TaskFrequencies::NONE,
                ]);

            Task::factory()
                ->count($numberOfTasks)
                ->for($user, 'creator')
                ->hasAttached($randomLabels)
                ->create([
                    'status' => TaskStatuses::COMPLETED,
                ]);
        }
    }
}
