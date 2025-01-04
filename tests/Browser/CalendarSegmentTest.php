<?php

namespace Tests\Browser;

use App\Enums\TaskFrequencies;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Components\TaskModal;
use Tests\DuskTestCase;

class CalendarSegmentTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * A Dusk test example.
     */
    public function test_can_see_calendar(): void
    {
        $user = User::factory()->hasTasks(15)->create();
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/calendar');
            $browser
                ->assertSee('Calendar');
        });
    }

    public function test_can_see_task_modal_for_date_and_create_task(): void
    {
        $user = User::factory()->create();
        $label = $user->labels()->create(['name' => 'not this one']);
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/calendar');
            $browser
                // enters modal and creates task
                ->click('td[data-date="' . now()->format('Y-m-d') . '"]')
                ->pause(800)
                ->within(new TaskModal, function (Browser $browser) {
                    $browser
                        ->assertInputValue('@date', now()->format('Y-m-d'))
                        ->type('@name', 'Task 1')
                        ->type('@description', 'Description 1')
                        // create label
                        ->click('@user-label-create-button')
                        ->pause(900)
                        ->type('@search-input', 'New Label')
                        ->pause(900)
                        ->press('Create')
                        ->pause(300)
                        // setting up priority
                        ->click('@priority-input')
                        ->pause(300)
                        ->clickAtXPath("//div[contains(text(), 'P1')] ")
                        ->pause(300)
                        ->press('Save Data');
                })
                // exists
                ->pause(1600)
                // enters the modal again to verify data is there
                ->assertSeeIn('@calendar-body', 'Task 1')
                ->clickAtXPath("//div[contains(text(), 'Task 1') and contains(@class,'fc-event-title')] ")
                ->pause(1500)
                ->within(new TaskModal, function (Browser $browser) {
                    $browser->assertInputValue('@name', 'Task 1');
                    $browser->assertInputValue('@date', now()->format('Y-m-d'));  // falla
                    $browser->assertInputValue('@description', 'Description 1');
                    $val = $browser->value('@project');
                    $this->assertEquals($val, '');
                    // dd($valDate);
                });
        });
    }

    public function test_can_enter_to_existing_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user, 'creator')->create();
        $this->browse(function (Browser $browser) use ($user, $task) {
            $browser->loginAs($user)->visit('/calendar');
            $browser
                ->pause(600)
                ->clickAtXPath("//div[contains(text(), '{$task->name}') and contains(@class,'fc-event-title')] ")
                ->pause(800)
                ->within(new TaskModal, function (Browser $browser) use ($task) {
                    $browser->assertInputValue('@date', $task->due_date);
                    $browser->assertInputValue('@name', $task->name);
                    $browser->assertInputValue('@description', $task->description);
                    $val = $browser->value('@project');
                    $this->assertEquals($val, $task->project->name ?? '');
                });
        });
    }

    public function test_can_drag_task_to_other_date(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user, 'creator')->create(['due_date' => now()]);

        $this->browse(function (Browser $browser) use ($user, $task) {
            $taskPreviousDate = $task->due_date;
            $browser->loginAs($user)->visit('/calendar');
            $browser
                ->pause(800)
                ->drag('.fc-event-title', '.fc-day[data-date="' . now()->addDays(1)->format('Y-m-d') . '"]')
                ->clickAtXPath("//div[contains(text(), '{$task->name}') and contains(@class,'fc-event-title')] ")
                ->pause(3000)
                ->within(new TaskModal, function (Browser $browser) use ($task) {
                    $browser->assertInputValue('@date', now()->addDays(1)->format('Y-m-d'));
                    $browser->assertInputValue('@name', $task->name);
                    $browser->assertInputValue('@description', $task->description);
                    $val = $browser->value('@project');
                    $this->assertEquals($val, $task->project->name ?? '');
                });
        });
    }

    public function test_completing_recurring_task_changes_its_location(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user, 'creator')->create(
            ['due_date' => now(), 'frequency' => TaskFrequencies::DAILY->value]
        );
        $this
            ->browse(function (Browser $browser) use ($user, $task) {
                $browser->loginAs($user)->visit('/calendar');
                $browser
                    ->pause(300)
                    ->clickAtXPath("//div[contains(text(), '{$task->name}') and contains(@class,'fc-event-title')] ")
                    ->pause(1200)
                    ->within(new TaskModal, function (Browser $browser) use ($task) {
                        $browser->assertInputValue('@date', $task->due_date);
                        $browser->assertInputValue('@name', $task->name);
                        $browser->assertInputValue('@description', $task->description);
                        $val = $browser->value('@project');
                        $this->assertEquals($val, $task->project->name ?? '');
                        $browser
                            ->check('@checkbox')
                            ->pause(1500);
                        $browser->assertInputValue('@date', now()->addDay()->format('Y-m-d'));
                        $browser->assertAttributeDoesntContain('@name', 'class', 'line-through');
                        $browser->assertNotChecked('@checkbox');
                        $browser->press('@close');
                    })
                    ->pause(700)
                    ->within('.fc-day[data-date="' . now()->addDays(1)->format('Y-m-d') . '"]',
                        function (Browser $browser) use ($task) {
                            $browser->assertSeeIn('.fc-event-title', $task->name);
                        });
            });
    }

    /**/
}
