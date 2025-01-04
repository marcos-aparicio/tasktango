<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
    #[Layout('layouts.guest')]
    class extends Component
    {
        public string $username = '';
        public string $full_name = '';
        public string $email = '';
        public string $password = '';
        public string $password_confirmation = '';

        public function mount(): void
        {
            session()->put('redirectURL', route('register'));
        }

        /**
         * Handle an incoming registration request.
         */
        public function register(): void
        {
            $validated = $this->validate([
                'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
                'full_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password' => ['required', 'string', 'confirmed', Rules\Password::defaults(), 'min:' . config('constants.password_min_length'), 'max:' . config('constants.password_max_length')],
            ]);

            $validated['password'] = Hash::make($validated['password']);

            event(new Registered(($user = User::create($validated))));

            Auth::login($user);

            $this->redirect(route('index', absolute: false), navigate: true);
        }
    };
?>

<div>
    <h2 class="text-2xl text-center font-semibold">Create an account</h2>
    <x-form wire:submit="register">
        @csrf
        <div class="px-6 py-4 flex justify-center">
            <x-oauth-component text="Register with Google" />
        </div>

        <!-- Name -->
        <x-input label="User Name" placeholder="i.e (user_485)" icon="o-user" wire:model="username" dusk="register-user-name"/>
        <!-- Name -->
        <x-input label="Full Name" placeholder="Your name" icon="o-user" wire:model="full_name" dusk="register-full-name"/>

        <!-- Email Address -->
        <x-input label="Email" placeholder="Your email address" icon="o-envelope" wire:model="email" dusk="register-email"/>

        <!-- Password -->
        <x-input label="Password" wire:model="password" icon="o-eye" type="password" dusk="register-password"
            hint="{{ config('constants.password_min_length') }} - {{ config('constants.password_max_length') }} characters" />

        <!-- Confirm Password -->
        <x-input label="Retype your password" wire:model="password_confirmation" icon="o-eye" type="password"
            dusk="register-confirm-password"
            hint="{{ config('constants.password_min_length') }} - {{ config('constants.password_max_length') }} characters" />

        <x-slot:actions>
            <a class="link link-secondary self-center" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>
            <x-button label="Register" class="btn btn-primary" type="submit" spinner="save" />
        </x-slot:actions>

    </x-form>
</div>
