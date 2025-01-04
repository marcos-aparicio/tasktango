<?php

namespace Tests\Browser;

use App\Enums\TaskFrequencies;
use App\Enums\TaskStatuses;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Components\AddTaskCard;
use Tests\Browser\Components\CompletedTaskCard;
use Tests\Browser\Components\CompletedTasksList;
use Tests\Browser\Components\TaskCard;
use Tests\Browser\Components\TaskModal;
use Tests\DuskTestCase;
use Closure;

class SegmentsTest extends DuskTestCase
{
    use DatabaseTruncation;

    private function monitor_prefilled(String $route, Closure $closure, User $user = null): void
    {
        if ($user == null)
            $user = User::factory()->create([
                'email' => 'example@test.com',
            ]);

        $this->browse(function (Browser $browser) use ($user, $route, $closure) {
            $browser
                ->loginAs($user)
                ->visit($route);
            $browser->within(new AddTaskCard, function ($browser) use ($closure) {
                $closure($browser);
            });
        });
    }

    public function test_today_prefilled_when_adding_tasks(): void
    {
        $this->monitor_prefilled('/today', function ($browser) {
            $browser->assertInputValue('@date', now()->format('Y-m-d'));
        });
    }

    public function test_next_7_days_prefilled_when_adding_tasks(): void
    {
        $this->monitor_prefilled('/next-7-days', function ($browser) {
            $browser->assertInputValue('@date', now()->format('Y-m-d'));
        });
    }

    public function test_inbox_not_prefilled_when_adding_tasks(): void
    {
        $this->monitor_prefilled('/inbox', function ($browser) {
            $browser->assertInputValue('@date', '');
        });
    }

    public function test_label_prefilled_when_adding_tasks(): void
    {
        $user = User::factory()->create();
        $label = $user->labels()->create(['name' => 'Label 1']);
        $this->monitor_prefilled('/label/' . $label->id, function ($browser) use ($label) {
            $browser
                ->press('New task')
                ->pause(600)
                ->press('Labels')
                ->pause(300)
                ->click('@individual-labels')
                ->assertSeeIn('.border-s-primary', $label->name);
        }, $user);
    }

    public function test_task_gets_added_to_label_view_when_created(): void
    {
        $user = User::factory()->create();
        $label = $user->labels()->create(['name' => 'Label 1']);

        $this->browse(function (Browser $browser) use ($user, $label) {
            $browser
                ->loginAs($user)
                ->visit('/label/' . $label->id);
            $browser
                ->within(new AddTaskCard, function ($browser) use ($label) {
                    $browser
                        ->press('New task')
                        ->pause(800)
                        ->type('@task-name', 'Task 1')
                        ->type('@task-description', 'Some description')
                        ->press('Labels')
                        ->click('@individual-labels')
                        // inside labels modal
                        ->pause(800)
                        ->assertSee($label->name)
                        ->press('Create')
                        ->type('@search-input', 'New Label')
                        ->pause(500)
                        ->click('@modal-label-create-buton-individual')
                        ->pause(500)
                        ->click('@individual-labels')
                        ->pause(500)
                        ->click('@labels-modal @labels-modal-cancel')
                        ->pause(500)
                        ->press('Add task');
                })
                ->pause(2000)
                ->assertSeeIn('@segment-body', 'Task 1');
        });
    }

    public function test_task_gets_added_to_inbox_view_when_created(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit('/inbox');
            $browser
                ->within(new AddTaskCard, function ($browser) {
                    $browser
                        ->press('New task')
                        ->pause(700)
                        ->type('@task-name', 'Task 1')
                        ->type('@task-description', 'Some description')
                        ->type('@task-description', 'Some description')
                        ->keys('@date', now()->addDays(4)->format('mdY'), '{escape}', '{escape}')
                        ->pause(700)
                        ->click('@priority-input')
                        ->pause(700)
                        ->clickAtXPath("//div[contains(text(), 'P1')] ")
                        ->pause(700)
                        ->press('Add task');
                })
                ->pause(3000)
                ->assertSeeIn('@segment-body', 'Task 1');
        });
    }

    public function test_task_gets_added_to_next_7_days_view_when_created(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit('/next-7-days');
            $duskGroup = sprintf('[dusk="group-title-[%s]"]', now()->addDays(4)->format('M j - l'));
            $browser
                ->within(new AddTaskCard, function ($browser) {
                    $browser
                        ->press('New task')
                        ->pause(500)
                        ->type('@task-name', 'Task 1')
                        ->pause(500)
                        ->type('@task-description', 'Some description')
                        ->keys('@date', now()->addDays(4)->format('mdY'), '{escape}', '{escape}')
                        ->pause(500)
                        ->press('Add task');
                })
                ->pause(1400)
                ->click($duskGroup)
                ->pause(1200)
                ->assertSeeIn($duskGroup, 'Task 1');
        });
    }

    public function test_task_gets_added_to_today_view_when_created(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit('/today');
            $browser
                ->within(new AddTaskCard, function ($browser) {
                    $browser
                        ->press('New task')
                        ->pause(500)
                        ->type('@task-name', 'Task 1')
                        ->type('@task-description', 'Some description')
                        ->pause(500)
                        ->press('Add task');
                })
                ->pause(1000)
                ->assertSeeIn('@segment-body', 'Task 1');
        });
    }

    public function test_task_doesnt_gets_added_to_next_7_days_view_when_created(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit('/next-7-days');
            $browser
                ->within(new AddTaskCard, function ($browser) {
                    $browser
                        ->press('New task')
                        ->pause(700)
                        ->type('@task-name', 'Task 1')
                        ->type('@task-description', 'Some description')
                        ->keys('@date', now()->addDays(9)->format('mdY'), '{escape}', '{escape}')
                        ->pause(700)
                        ->press('Add task');
                })
                ->pause(1200)
                ->assertDontSee('@segment-body', 'Task 1');
        });
    }

    public function test_task_doesnt_gets_added_to_today_view_when_created(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit('/today');
            $browser
                ->within(new AddTaskCard, function ($browser) {
                    $browser
                        ->press('New task')
                        ->pause(600)
                        ->type('@task-name', 'Task 1')
                        ->type('@task-description', 'Some description')
                        ->keys('@date', now()->addDays(4)->format('mdY'), '{escape}', '{escape}')
                        ->pause(600)
                        ->press('Add task');
                })
                ->pause(1200)
                ->assertDontSee('@segment-body', 'Task 1');
        });
    }

    public function test_task_doesnt_gets_added_to_label_view_when_created(): void
    {
        $user = User::factory()->create();
        $label = $user->labels()->create(['name' => 'Label 1']);

        $this->browse(function (Browser $browser) use ($user, $label) {
            $browser
                ->loginAs($user)
                ->visit('/label/' . $label->id);
            $browser
                ->within(new AddTaskCard, function ($browser) use ($label) {
                    $browser
                        ->press('New task')
                        ->pause(600)
                        ->type('@task-name', 'Task 1')
                        ->type('@task-description', 'Some description')
                        ->press('Labels')
                        // inside labels modal
                        ->pause(600)
                        // clear previous labels
                        ->click('@clear-choices')
                        ->pause(600)
                        ->press('Create')
                        ->pause(600)
                        // create new label(correct way)
                        ->type('@search-input', 'New Label')
                        ->pause(600)
                        ->click('@modal-label-create-buton-individual')
                        ->pause(600)
                        ->click('@individual-labels')
                        ->pause(600)
                        ->click('@labels-modal-cancel')
                        ->pause(600)
                        ->press('Add task');
                })
                ->pause(1000)
                ->assertDontSeeIn('@segment-body', 'Task 1');
        });
    }

    public function test_frequent_tasks_get_added_when_completed(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create([
            'name' => 'Task 1',
            'frequency' => TaskFrequencies::DAILY->value,
            'due_date' => now()->format('Y-m-d'),
        ]);
        $user->tasks()->create([
            'name' => 'Task 2',
            'frequency' => TaskFrequencies::DAILY->value,
            'due_date' => now()->format('Y-m-d'),
        ]);

        $this->browse(function (Browser $browser) use ($user, $task) {
            $browser
                ->loginAs($user)
                ->visit('/next-7-days');
            $duskGroup = sprintf('[dusk="group-title-[%s]"]', now()->format('M j - l'));
            $duskGroup2 = sprintf('[dusk="group-title-[%s]"]', now()->addDays(1)->format('M j - l'));
            $browser
                ->pause(500)
                ->click($duskGroup)
                ->pause(800)
                ->within(new TaskCard($task->id), function ($browser) {
                    $browser
                        ->assertSee('Task 1')
                        ->assertSee(now()->format('Y-m-d'))
                        ->assertNotChecked('@checkbox')
                        ->click('@checkbox');
                })
                ->pause(500)
                ->click($duskGroup2)
                ->pause(500)
                ->within(new TaskCard($task->id), function ($browser) {
                    $browser
                        ->assertSee('Task 1')
                        ->assertSee(now()->addDays(1)->format('Y-m-d'))
                        ->assertNotChecked('@checkbox');
                });
        });
    }

    public function test_uncompleting_completed_task_to_update_to_its_view(): void
    {
        $user = User::factory()->create();
        $tasks = Task::factory()->count(5)->for($user, 'creator')->create([
            'frequency' => TaskFrequencies::NONE->value,
        ]);
        $completedTasks = Task::factory()->count(3)->for($user, 'creator')->create([
            'frequency' => TaskFrequencies::NONE->value,
            'status' => TaskStatuses::COMPLETED->value,
        ]);

        $this->browse(function (Browser $browser) use ($user, $tasks, $completedTasks) {
            $browser
                ->loginAs($user)
                ->visit('/inbox');
            $browser
                ->assertSee($tasks->count())
                ->pause(300)
                ->within(new CompletedTasksList, function ($browser) use ($tasks, $completedTasks) {
                    $browser->assertSee('Completed tasks');
                    $browser->assertSee($completedTasks->count() . ' tasks');
                    $browser->collapse()->pause(500);
                    $browser
                        ->assertSee($completedTasks[0]->name)
                        ->within(new CompletedTaskCard($completedTasks[0]->id), function ($browser) use ($completedTasks) {
                            $browser->click('p');
                        });
                })
                ->pause(1200);
            $browser->within(new TaskModal, function ($browser) use ($completedTasks) {
                $browser
                    ->assertInputValue('@name', $completedTasks[0]->name)
                    ->assertInputValue('@description', $completedTasks[0]->description)
                    ->click('@checkbox')
                    ->click('@close')
                    ->pause(1200);
            });
            $browser->within(new CompletedTasksList, function ($browser) use ($tasks, $completedTasks) {
                $browser->assertSee('Completed tasks');
                $browser->assertSee(($completedTasks->count() - 1) . ' tasks');
                $browser->assertDontSee($completedTasks[0]->name);
            });
            $browser->assertSee(($tasks->count() + 1) . ' tasks');
            $browser->assertSee($completedTasks[0]->name);
        });
    }

    public function test_completed_task_gets_updated_to_its_list_and_deleted_from_main_one(): void
    {
        $user = User::factory()->create();
        $label = $user->labels()->create(['name' => 'Label 1']);
        $tasks = Task::factory()->count(5)->for($user, 'creator')->hasAttached($label)->create([
            'frequency' => TaskFrequencies::NONE->value,
        ]);

        $this->browse(function (Browser $browser) use ($user, $tasks, $label) {
            $browser
                ->loginAs($user)
                ->visit('/label/' . $label->id);
            $browser
                ->assertSee($tasks->count())
                ->assertDontSee(new CompletedTasksList)
                ->within(new TaskCard($tasks[0]->id), function ($browser) use ($tasks) {
                    $browser
                        ->assertSee($tasks[0]->name)
                        ->assertSee($tasks[0]->due_date)
                        ->assertNotChecked('@checkbox')
                        ->click('@checkbox');
                })
                ->pause(1800)
                ->within(new CompletedTasksList, function ($browser) use ($tasks) {
                    $browser->assertSee('Completed tasks');
                    $browser->assertSee('1 tasks');
                    $browser->collapse()->pause(500);
                    $browser
                        ->assertSee($tasks[0]->name)
                        ->pause(500);
                });
        });
    }

    // **
}
