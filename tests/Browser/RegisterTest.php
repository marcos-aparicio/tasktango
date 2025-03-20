<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\Browser;
use Tests\Browser\Components\Sidebar;
use Tests\DuskTestCase;

class RegisterTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $userEmail = 'marcos@marcos.com';  // Store email as a class property

    /**
     * Common registration method
     */
    protected function register_user_and_get_verification_url(): string
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/register')
                ->type('@register-user-name', 'randomUser239')
                ->type('@register-full-name', 'Marcos Aparicio')
                ->type('@register-email', $this->userEmail)  // Use class-level email
                ->type('@register-password', 'password')
                ->type('@register-confirm-password', 'password')
                ->press('Register')
                ->pause(2000);
            $browser->assertSee('Resend Verification Email');
            $this->assertDatabaseHas('users', [
                'email' => $this->userEmail,  // Use class-level email
                'email_verified_at' => null,
            ]);
        });

        // Retrieve the newly created user
        $user = User::where('email', $this->userEmail)->firstOrFail();  // Use class-level email

        // Generate the email verification URL directly from the user
        $verificationUrl = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        // Ensure we got the verification URL
        $this->assertNotEmpty($verificationUrl, 'Failed to generate verification URL.');

        return $verificationUrl;
    }

    public function test_user_can_register_without_setting_profile_picture(): void
    {
        $verificationUrl = $this->register_user_and_get_verification_url();
        // Now visit the email verification link, which will redirect to the set profile picture component
        // skip the selection
        $this->browse(function (Browser $browser) use ($verificationUrl) {
            $browser
                ->visit($verificationUrl)
                ->press('Skip for Now')
                ->pause(1000)
                ->assertUrlIs(route('inbox'));
        });
    }

    public function test_user_can_register_while_setting_profile_picture(): void
    {
        $verificationUrl = $this->register_user_and_get_verification_url();
        $user = User::where('email', $this->userEmail)->firstOrFail();  // Use class-level email

        $fakePic = UploadedFile::fake()->image('test_image.jpg', 200, 200);
        $this->browse(function (Browser $browser) use ($verificationUrl, $fakePic, $user) {
            $browser
                ->visit($verificationUrl)
                ->attach(
                    'profile-picture',
                    $fakePic->getPathname(),
                )
                ->pause(1400)
                ->assertVisible('img')
                ->press('Set Picture')
                ->pause(1000)
                ->assertUrlIs(route('inbox'))
                ->within(new Sidebar, function (Browser $browser) use ($user) {
                    $user = User::where('email', $user->email)->firstOrFail();
                    $browser
                        ->assertAttribute('@profile-avatar img', 'src', $user->getProfilePictureUrl());
                });
        });
    }
}
