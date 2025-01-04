<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
    #[Layout('layouts.guest')]
    class extends Component
    {
        public LoginForm $form;

        public function mount(): void
        {
            session()->put('redirectURL', route('login'));
        }

        /**
         * Handle an incoming authentication request.
         */
        public function login(): void
        {
            $this->validate();

            $this->form->authenticate();

            Session::regenerate();

            $this->redirectIntended(default: route('index', absolute: false), navigate: true);
        }
    };
?>

<div>
    <!-- Session Status -->
    @if (session('status'))
        <x-alert icon="o-check" class="alert-success my-2" :title="session('status')"/>
    @endif
    <h2 class="text-2xl text-center font-semibold">Log in</h2>

    <x-form wire:submit="login">
        @csrf
        <!-- Email Address -->
        <div class="px-6 py-4 flex justify-center">
            <x-oauth-component text="Sign in with Google" />
        </div>
        <div>
            <x-input label="Email" placeholder="Your email address" icon="o-envelope" wire:model="form.email" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input label="Password" wire:model="form.password" icon="o-eye" type="password"
                hint="{{ config('constants.password_min_length') }} - {{ config('constants.password_max_length') }} characters" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4 select-none">
            <x-checkbox label="Remember me" wire:model="form.remember" />
        </div>
        <div class="block mt-4 text-sm">
            <a class="link link-secondary" href="{{ route('register') }}" wire:navigate>
                Do not have an account?
            </a>
        </div>


        <x-slot:actions>
        @if (Route::has('password.request'))
            <a class="link link-secondary self-center"
                href="{{ route('password.request') }}" wire:navigate>
                {{ __('Forgot your password?') }}

            </a>
        @endif
            <x-button label="Log in" class="btn btn-primary" type="submit" spinner="save" />
        </x-slot:actions>

    </x-form>
</div>
