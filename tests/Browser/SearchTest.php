<?php

namespace Tests\Browser;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SearchTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_search_filters_correctly_by_name(): void
    {
        $user = User::factory()->create();

        $triggerWord = 'ozymandias';
        $tasks1 = Task::factory()
            ->count(5)
            ->sequence(fn(Sequence $sequence) => ['name' => "Ozymandias {$sequence->index}"])
            ->for($user, 'creator')
            ->create();

        $tasks2 = Task::factory()
            ->count(5)
            ->for($user, 'creator')
            ->create();

        $this->browse(function (Browser $browser) use ($user, $tasks1, $tasks2, $triggerWord) {
            $browser->loginAs($user);
            $browser
                ->visit(route('search'))
                ->pause(500)
                ->type('@task-name-search', $triggerWord)
                ->pause(400)
                ->press('Search')
                ->pause(800)
                ->assertSee("Tasks found: {$tasks1->count()}");
            for ($i = 0; $i < $tasks1->count(); $i++) {
                $browser->assertSee($tasks1[$i]->name)->assertDontSee($tasks2[$i]->name);
            }
        });
    }

    public function test_search_filters_correctly_by_label(): void
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

        $this->browse(function (Browser $browser) use ($user, $userLabel1, $userLabel2, $tasks1, $tasks2) {
            $browser->loginAs($user);
            $browser
                ->visit(route('search'))
                ->pause(500)
                ->click('@task-filter-collapse')
                ->pause(400)
                ->click('@task-filter-collapse @individual-labels')
                ->pause(400)
                ->clickAtXPath("//div[contains(text(), '" . $userLabel1->name . "')] ")
                ->pause(400)
                ->press('Search')
                ->pause(1600)
                ->assertSee("Tasks found: {$tasks1->count()}");
            for ($i = 0; $i < $tasks1->count(); $i++) {
                $browser->assertSee($tasks1[$i]->name)->assertDontSee($tasks2[$i]->name);
            }
        });
    }
}
