<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Volt\Volt;
use Tests\TestCase;
use Mockery;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('index', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_oauth_nickname_occupied(): void
    {
        $user = User::factory()->create();
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser
            ->shouldReceive('getId')
            ->andReturn(Str::random(10))
            ->shouldReceive('getName')
            ->andReturn(Str::random(10))
            ->shouldReceive('getNickname')
            ->andReturn($user->username)
            ->shouldReceive('getEmail')
            ->andReturn(strtolower(Str::random(10) . '@gmail.com'))
            ->shouldReceive('getAvatar')
            ->andReturn('https://images.pexels.com/photos/3560168/pexels-photo-3560168.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $response
            ->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
    }

    public function test_oauth_email_occupied(): void
    {
        $user = User::factory()->create();
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser
            ->shouldReceive('getId')
            ->andReturn(Str::random(10))
            ->shouldReceive('getName')
            ->andReturn(Str::random(10))
            ->shouldReceive('getNickname')
            ->andReturn(null)
            ->shouldReceive('getEmail')
            ->andReturn($user->email)
            ->shouldReceive('getAvatar')
            ->andReturn('https://images.pexels.com/photos/3560168/pexels-photo-3560168.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $response
            ->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
    }

    public function test_user_can_authenticate_using_oauth(): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser
            ->shouldReceive('getId')
            ->andReturn(Str::random(10))
            ->shouldReceive('getName')
            ->andReturn(Str::random(10))
            ->shouldReceive('getNickname')
            ->andReturn(null)
            ->shouldReceive('getEmail')
            ->andReturn(strtolower(Str::random(10) . '@gmail.com'))
            ->shouldReceive('getAvatar')
            ->andReturn('https://images.pexels.com/photos/3560168/pexels-photo-3560168.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $response
            ->assertRedirect(route('root', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => $abstractUser->getEmail(),
            'full_name' => $abstractUser->getName(),
            'username' => $abstractUser->getName(),
            'provider' => 'google',
            'provider_id' => $abstractUser->getId(),
        ]);
        $this->assertAuthenticated();

        $user = User::where('email', $abstractUser->getEmail())->first();
        Storage::disk('public')->assertExists($user->profile_picture);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_sidebar_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->followingRedirects()->get(route('index'));

        $response
            ->assertOk()
            ->assertSee('Collapse');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this
            ->get(route('logout'))
            ->assertRedirect(route('root', absolute: false));

        $this->assertGuest();
    }

    public function test_cant_login_by_oauth_when_user_email_has_been_created_by_normal_methods(): void
    {
        $user = User::factory()->create();
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser
            ->shouldReceive('getId')
            ->andReturn(Str::random(10))
            ->shouldReceive('getName')
            ->andReturn(Str::random(10))
            ->shouldReceive('getEmail')
            ->andReturn($user->email)
            ->shouldReceive('getAvatar')
            ->andReturn('https://images.pexels.com/photos/3560168/pexels-photo-3560168.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertGuest();
        $response->assertSessionHasErrors(['form.email', 'email']);
    }

    function test_cant_login_by_normal_methods_when_registered_by_oauth(): void
    {
        $user = User::factory()->asOauth()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');
        $component->assertHasErrors('form.email');
        $this->assertGuest();
    }
}
