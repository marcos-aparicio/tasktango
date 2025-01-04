<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * A basic browser test example.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'taylor@laravel.com',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser
                ->visit('/login')
                ->type('input[type="text"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->press('Log in')
                ->pause(2000)
                ->assertAuthenticated();
        });
    }
}
