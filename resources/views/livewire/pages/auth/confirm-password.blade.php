<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
    #[Layout('layouts.guest', ['hideBackButton' => true])]
    class extends Component
    {
        public string $password = '';

        /**
         * Confirm the current user's password.
         */
        public function confirmPassword(): void
        {
            $this->validate([
                'password' => ['required', 'string'],
            ]);

            if (
                !Auth::guard('web')->validate([
                    'email' => Auth::user()->email,
                    'password' => $this->password,
                ])
            ) {
                throw ValidationException::withMessages([
                    'password' => __('auth.password'),
                ]);
            }

            session(['auth.password_confirmed_at' => time()]);

            $this->redirectIntended(default: route('index', absolute: false), navigate: true);
        }

        /**
         * Log the current user out of the application.
         */
        public function logout(Logout $logout): void
        {
            $logout();

            $this->redirect('/', navigate: true);
        }
    };
?>

<div>
    <div class="mb-4 text-sm text-secondary">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <x-form wire:submit="confirmPassword">
        @csrf
        <!-- Password -->
        <x-input label="Password" wire:model="password" icon="o-eye" type="password" />
        <x-slot:actions>
            <x-button wire:click="logout" label="Log Out" class="btn-outline" />
            <x-button type="submit" label="Confirm" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</div>
