<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->success(title: 'Password Updated Successfully', position: 'toast-bottom toast-end text-wrap');
    }
};
?>

<section>
    <header>
        <h2 class="text-lg font-medium text-primary">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-secondary">
            Ensure your account is using a long, random password to stay secure. It must be between
            {{ config('constants.password_min_length') }} and {{ config('constants.password_max_length') }} characters.
        </p>
    </header>


    <x-form wire:submit="updatePassword" class="mt-6">
        @csrf
        <x-input label="Current Password" wire:model="current_password" icon="o-eye" type="password" />

        <x-input label="New Password" wire:model="password" icon="o-eye" type="password"
            hint="{{ config('constants.password_min_length') }} - {{ config('constants.password_max_length') }} characters" />

        <x-input label="Confirm Password" wire:model="password_confirmation" icon="o-eye" type="password"
            hint="{{ config('constants.password_min_length') }} - {{ config('constants.password_max_length') }} characters" />

        <x-slot:actions>
            <x-button label="Save" class="btn-primary" type="submit" />
        </x-slot:actions>

    </x-form>
</section>
