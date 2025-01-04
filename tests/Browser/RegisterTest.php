<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RegisterTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * A basic browser test example.
     */
    public function test_user_can_register(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/register')
                ->type('@register-user-name', 'randomUser239')
                ->type('@register-full-name', 'Marcos Aparicio')
                ->type('@register-email', 'marcos@marcos.com')
                ->type('@register-password', 'password')
                ->type('@register-confirm-password', 'password')
                ->press('Register')
                ->pause(2000);
            $browser->assertSee('Resend Verification Email');
            $this->assertDatabaseHas('users', [
                'email' => 'marcos@marcos.com',
                'email_verified_at' => null,
            ]);
        });
    }
}
