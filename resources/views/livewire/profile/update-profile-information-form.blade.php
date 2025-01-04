<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $email = '';
    public string $full_name = '';
    public string $username = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->email = Auth::user()->email;
        $this->full_name = Auth::user()->full_name;
        $this->username = Auth::user()->username;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'full_name' => ['required', 'string', 'max:255'],
        ]);

        $user->fill($validated);
        $user->save();
        $this->success(title: 'Profile Updated Successfully', position: 'toast-bottom toast-end text-wrap');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('index', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        $this->success(
            title: 'Verification Email Sent',
            position: 'toast-bottom toast-end text-wrap'
        );
    }
};
?>



<x-form wire:submit="updateProfileInformation" class="mt-6 flex flex-col gap-4">
    <!-- Name -->
    <x-input label="User Name (read only)" disabled icon="o-user" wire:model="username" />
    <x-input label="Full Name" icon="o-user" wire:model="full_name" />

    <div>
        <!-- Email Address -->
        <x-input label="Email (read only)" disabled placeholder="Your email address" icon="o-envelope" wire:model="email" class="overflow-x-scroll"/>

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <div>
                <p class="mt-2 text-base">
                    {{ __('Your email address is unverified.') }}
                    <x-button label="Click here to re-send the verification email" class="btn-ghost" wire:click.prevent="sendVerification"/>
                </p>
            </div>
        @endif
    </div>
<x-slot:actions>
    <x-button label="Save" class="btn-primary" type="submit"/>
</x-slot:actions>
</x-form>

