<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('full_name', 'Test User')
            ->set('username', 'test-user823')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('index', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_cant_create_user_with_same_email_account_as_another_user(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.register')
            ->set('full_name', 'Test User')
            ->set('username', 'test-user823')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_cant_create_user_with_same_username_as_another_user(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.register')
            ->set('full_name', 'Test User')
            ->set('username', $user->username)
            ->set('email', fake()->email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component
            ->assertHasErrors('username');

        $this->assertGuest();
    }
}
