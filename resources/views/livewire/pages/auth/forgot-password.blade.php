<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
    #[Layout('layouts.guest')]
    class extends Component
    {
        public string $email = '';

        /**
         * Send a password reset link to the provided email address.
         */
        public function sendPasswordResetLink(): void
        {
            $this->validate([
                'email' => ['required', 'string', 'email'],
            ]);

            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $status = Password::sendResetLink($this->only('email'));

            if ($status != Password::RESET_LINK_SENT) {
                $this->addError('email', __($status));

                return;
            }

            $this->reset('email');

            session()->flash('status', __($status));
        }
    };
?>

<div>
    <h2 class="text-2xl text-center font-semibold pb-4">Recover your account</h2>

    <div class="mb-4 text-sm text-secondary">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <x-alert icon="o-check" class="alert-success my-2" :title="session('status')"/>
    @endif

    <x-form wire:submit="sendPasswordResetLink">
        @csrf
        <!-- Email Address -->
        <x-input label="Email" placeholder="Your email address" icon="o-envelope" wire:model="email" />

        <x-slot:actions>
            <button class="btn btn-primary">
                {{ __('Email Password Reset Link') }}
            </button>
        </x-slot:actions>
    </x-form>
</div>
