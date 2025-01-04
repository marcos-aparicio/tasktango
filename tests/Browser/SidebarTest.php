<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Components\Sidebar;
use Tests\DuskTestCase;

class SidebarTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_can_see_sidebar(): void
    {
        $user = User::factory()->create([
            'email' => 'taylor@laravel.com',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->loginAs($user)
                ->visit('/inbox');
            $browser->assertPresent('#main-sidebar');
        });
    }

    public function test_can_change_theme(): void
    {
        $user = User::factory()->create([
            'email' => 'taylor@laravel.com',
        ]);
        $millisToWait = 300;

        $this->browse(function (Browser $browser) use ($user, $millisToWait) {
            $browser->loginAs($user)->visit('/inbox');
            $prevMaryTheme = $browser->script('return window.localStorage.getItem("mary-theme")')[0];

            // // changing theme
            $browser = $browser
                ->within(new Sidebar, function ($browser) {
                    $browser->toggleTheme();
                });

            $currentMaryTheme = $browser->script('return window.localStorage.getItem("mary-theme")')[0];
            $this->assertNotSame($currentMaryTheme, $prevMaryTheme);
        });
    }

    public function test_sidebar_logs_out(): void
    {
        $user = User::factory()->create([
            'email' => 'taylor@laravel.com',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/inbox');

            // // changing theme
            $browser = $browser
                ->within(new Sidebar, function ($browser) {
                    $browser->click('@logout');
                })
                ->assertPathIsNot('/inbox')
                ->assertGuest();
        });
    }

    public function test_sidebar_redirects_to_inbox(): void
    {
        $user = User::factory()->create([
            'email' => 'taylor@laravel.com',
        ]);
        $millisToWait = 300;

        $this->browse(function (Browser $browser) use ($user, $millisToWait) {
            $browser->loginAs($user)->visit('/next-7-days');

            // // changing theme
            $browser = $browser
                ->within(new Sidebar, function ($browser) {
                    $browser->click('@inbox')->pause(800);
                })
                ->assertPathIs('/inbox');
        });
    }

    public function test_sidebar_redirects_to_next_7_days(): void
    {
        $user = User::factory()->create([
            'email' => 'taylor@laravel.com',
        ]);
        $millisToWait = 300;

        $this->browse(function (Browser $browser) use ($user, $millisToWait) {
            $browser->loginAs($user)->visit('/inbox');

            // // changing theme
            $browser = $browser
                ->within(new Sidebar, function ($browser) {
                    $browser->click('@next-7-days')->pause(800);
                })
                ->assertPathIs('/next-7-days');
        });
    }
}
